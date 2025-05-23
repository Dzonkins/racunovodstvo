<?php

namespace Drupal\eck\Form\EntityType;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form base for configuring ECK entity types.
 *
 * @ingroup eck
 */
class EckEntityTypeFormBase extends EntityForm {

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $eckEntityTypeStorage;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Construct the EckEntityTypeFormBase.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $eck_entity_type_storage
   *   The eck_entity_type storage.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(EntityStorageInterface $eck_entity_type_storage, EntityFieldManagerInterface $entity_field_manager, MessengerInterface $messenger, ConfigFactoryInterface $configFactory) {
    $this->eckEntityTypeStorage = $eck_entity_type_storage;
    $this->entityFieldManager = $entity_field_manager;
    $this->messenger = $messenger;
    $this->configFactory = $configFactory;
  }

  /**
   * Factory method for EckEntityTypeFormBase.
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('eck_entity_type'),
      $container->get('entity_field.manager'),
      $container->get('messenger'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the from from the base class.
    $form = parent::buildForm($form, $form_state);

    $eck_entity_type = $this->entity;

    // Build the form.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $eck_entity_type->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#maxlength' => 32,
      '#default_value' => $eck_entity_type->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ],
      '#disabled' => !$eck_entity_type->isNew(),
    ];

    $form['base_fields'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Available base fields'),
    ];

    $config = $this->configFactory->get('eck.eck_entity_type.' . $eck_entity_type->id());
    foreach (['created', 'changed', 'uid', 'title', 'status'] as $field) {
      $title = $field === 'uid' ? 'author' : $field;

      $form['base_fields'][$field] = [
        '#type' => 'checkbox',
        '#title' => $this->t('%field field', ['%field' => ucfirst($title)]),
        '#default_value' => $config->get($field),
      ];
    }

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
    ];

    $form['settings']['standalone_url'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Standalone entity URL'),
      '#description' => $this->t('Allow entities to be viewed standalone'),
      '#default_value' => $eck_entity_type->hasStandaloneUrl(),
    ];

    return $form;
  }

  /**
   * Checks for an existing ECK entity type.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if this format already exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    // Use the query factory to build a new event entity query.
    $query = $this->eckEntityTypeStorage->getQuery()->accessCheck(FALSE);

    // Query the entity ID to see if its in use.
    $result = $query->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();

    // We don't need to return the ID, only if it exists or not.
    return (bool) $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Get the basic actions from the base class.
    $actions = parent::actions($form, $form_state);

    // Change the submit button text.
    $actions['submit']['#value'] = $this->t('Save');

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // The entity object is already populated with the values from the form.
    $status = $this->entity->save();

    $messageArgs = ['%label' => $this->entity->label()];
    $message = $this->t('Entity type %label has been added.', $messageArgs);
    if ($status === SAVED_UPDATED) {
      $message = $this->t('Entity type %label has been updated.', $messageArgs);
    }
    $this->messenger->addMessage($message);

    // Redirect the user back to the listing route after the save operation.
    $form_state->setRedirect('eck.entity_type.list');
    return $status;
  }

}

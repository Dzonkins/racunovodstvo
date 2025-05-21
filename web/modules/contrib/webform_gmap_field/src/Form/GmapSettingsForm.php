<?php

namespace Drupal\webform_gmap_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Google Maps API settings form.
 */
class GmapSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_gmap_field_gmap_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'webform_gmap_field.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API Key'),
      '#default_value' => \Drupal::config('webform_gmap_field.config')->get('api_key'),
      '#description' => $this->t('Enter your Google Maps API key here.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the API key to the module's configuration.
    \Drupal::configFactory()
      ->getEditable('webform_gmap_field.config')
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();

    // Optionally display a success message.
    \Drupal::messenger()->addStatus($this->t('Google Maps API key saved successfully.'));

    // Clear Drupal's cache.
    drupal_flush_all_caches();
  }

}

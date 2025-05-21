<?php

namespace Drupal\webform_gmap_field\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'webform_gmap_field' element.
 *
 * @WebformElement(
 *   id = "webform_gmap_field",
 *   label = @Translation("Webform Gmap Field"),
 *   description = @Translation("Provides a webform Google map field
 *     allowing to select user location and collect location coordinates."),
 *   category = @Translation("Google Map"),
 *   composite = TRUE
 * )
 *
 * @see \Drupal\webform_gmap_field\Element\WebformExampleElement
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class WebformGmapField extends WebformCompositeBase {

  /**
   * We're altering the table column output.
   */
  public function formatTableColumn(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if (isset($options['composite_key']) && isset($options['composite_element'])) {
      $composite_element = $options['composite_element'];
      $composite_element['#webform_key'] = $element['#webform_key'];
      return $this->elementManager->invokeMethod('formatHtml', $composite_element, $webform_submission, $options);
    }
    else {
      return $this->formatText($element, $webform_submission);
    }
  }

  /**
   * {@inheritdoc}
   *
   * We are hiding the hidden elements from the configuration form
   * since they are not configurable.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['composite']['element']['#access'] = FALSE;
    $form['custom']['properties']['#description'] .= '<br /><br />' .
        $this->t("You can set sub-element properties using a double underscore between the sub-element's key and sub-element's property (subelement__property). For example, you can add custom attributes or states (conditional logic) to the title sub-element using 'title__attributes' and 'title__states'.");
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    $lines = [];
    $webform_key = $element["#webform_key"];
    $lines['coords'] = [
      '#prefix' => '<div class="coordinates">',
      '#suffix' => '</div>',
      'label' => [
        '#prefix' => '<label>',
        '#suffix' => ':</label>',
        '#markup' => $this->t('Coordinates'),
      ],
      'data' => [
        '#markup' => (!empty($value['lat']) ? $value['lat'] : '') .
        (!empty($value['lng']) ? ':' . $value['lng'] : ''),
      ],
    ];

    $lines['map'] = [
      '#prefix' => '<div id="' . $webform_key . '-map" class="webform-gmap-field-canvas">',
      '#suffix' => '</div>',
      '#markup' => $this->t('Map loading..'),
    ];

    $lines['map']['#attached']['library'][] = 'webform_gmap_field/global';
    $lines['map']['#attached']['drupalSettings']['webform_gmap_field'][$webform_key]['editable'] = FALSE;
    $lines['map']['#attached']['drupalSettings']['webform_gmap_field'][$webform_key]['user_location'] = json_encode(
          [
            'lat' => (float) $value['lat'],
            'lng' => (float) $value['lng'],
          ]
      );

    return $lines;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $lines = [];
    $lines['coords'] = (!empty($value['lat']) ? $value['lat'] : '') .
        (!empty($value['lng']) ? ':' . $value['lng'] : '');

    return $lines;
  }

}

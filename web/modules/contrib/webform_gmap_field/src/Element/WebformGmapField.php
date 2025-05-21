<?php

namespace Drupal\webform_gmap_field\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'webform_gmap_field'.
 *
 * Webform elements are just wrappers around form elements, therefore every
 * webform element must have correspond FormElement.
 *
 * Below is the definition for a custom 'webform_gmap_field' which just
 * renders a simple text field.
 *
 * @FormElement("webform_gmap_field")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 * @see \Drupal\webform_gmap_field\Element\WebformGmapElement
 */
class WebformGmapField extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];
    if (isset($element["#webform_key"])) {
      $webform_key = $element["#webform_key"];

      $elements['lat'] = [
        '#type' => 'hidden',
        '#title' => t('Latitude'),
      ];
      $elements['lng'] = [
        '#type' => 'hidden',
        '#title' => t('Longitude'),
      ];

      $elements['map'] = [
        '#type' => 'markup',
        '#title' => !empty($element['#title']) ? $element['#title'] : '',
        '#markup' => '<div id="' . $webform_key . '-map" class="webform-gmap-field-canvas">map loading..</div>',
      ];

      $user_location = [];
      if (!empty($element['#value']['lat'])
            && !empty($element['#value']['lng'])
        ) {
        $user_location = [
          'lat' => (float) $element['#value']['lat'],
          'lng' => (float) $element['#value']['lng'],
        ];
      }

      $elements['#attached']['library'][] = 'webform_gmap_field/global';
      $elements['#attached']['drupalSettings']['webform_gmap_field'][$webform_key]['editable'] = TRUE;
      $elements['#attached']['drupalSettings']['webform_gmap_field'][$webform_key]['user_location'] = json_encode($user_location);
    }

    return $elements;
  }

}

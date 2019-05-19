<?php

namespace Drupal\visualn_embed\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Render\Element\Radios;

/**
 * Provides a form element for a set of drawing radio buttons.
 *
 * Instead of plain text as it is for generic radio buttons, drawing labels
 * are arrays of drawings related data which gets wrapped into a template and
 * rendered before rendering radio buttons themselves.
 *
 * @see \Drupal\Core\Render\Element\Radios
 * @see \Drupal\Core\Render\Element\Checkboxes
 * @see \Drupal\Core\Render\Element\Radio
 * @see \Drupal\Core\Render\Element\Select
 *
 * @FormElement("drawing_radios")
 */
class DrawingOptions extends Radios {

  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    if (count($element['#options']) > 0) {
      $new_options = [];

      // @todo: instantiate at class create or in __construct()
      $renderer = \Drupal::service('renderer');
      foreach ($element['#options'] as $key => $option) {
        // render theme wrapper for each option label
        $option_label = [
          '#theme' => 'visualn_embed_drawing_select_item_label',
          '#id' => $option['id'],
          '#name' => $option['name'],
          '#thumbnail_path' => $option['thumbnail_path'],
          '#preview_link' => $option['preview_link'],
          '#edit_link' => $option['edit_link'],
          '#delete_link' => $option['delete_link'],
        ];
        $new_options[$key] = $renderer->render($option_label);
      }

      $element['#options'] = $new_options;
    }

    $element = parent::processRadios($element, $form_state, $complete_form);

    return $element;
  }

}

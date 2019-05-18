<?php

namespace Drupal\pcr\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'options_pretty' widget.
 *
 * @FieldWidget(
 *   id = "options_pretty",
 *   label = @Translation("Pretty Check boxes/radio buttons"),
 *   field_types = {
 *     "list_string",
 *   },
 *   multiple_values = TRUE
 * )
 */
class PrettyOptionsWidget extends OptionsButtonsWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Set the #pretty_option for "Pretty Check boxes/radio buttons" Widget.
    $element['#pretty_option'] = TRUE;
    $form['#attached']['library'][] = 'pcr/pretty_options';

    return $element;
  }

  /**
   * Processes a checkboxes and radios form element.
   */
  public static function processPrettyElements(&$element, FormStateInterface $form_state, &$complete_form) {
    if (isset($element['#pretty_option'])) {
      foreach ($element['#options'] as $key => $choice) {
        // Set to pretty_checkbox or pretty_radio element type.
        $element[$key]['#type'] = 'pretty_' . $element[$key]['#type'];
      }
    }
    return $element;
  }

}

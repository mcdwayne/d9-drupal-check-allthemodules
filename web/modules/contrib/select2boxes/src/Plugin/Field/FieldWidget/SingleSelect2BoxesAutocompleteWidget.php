<?php

namespace Drupal\select2boxes\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\select2boxes\AutoCreationProcessTrait;
use Drupal\select2boxes\FlatteningOptionsTrait;
use Drupal\select2boxes\MinSearchLengthTrait;

/**
 * Class SingleSelect2BoxesAutocompleteWidget.
 *
 * @FieldWidget(
 *   id = "select2boxes_autocomplete_single",
 *   label = @Translation("Select2 boxes (Single value)"),
 *   description = @Translation("Single select2 boxes for entity reference fields"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 * @package Drupal\select2boxes\Plugin\Field\FieldWidget
 */
class SingleSelect2BoxesAutocompleteWidget extends OptionsSelectWidget {
  use MinSearchLengthTrait;
  use FlatteningOptionsTrait;
  use AutoCreationProcessTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $this->flatteningOptions($element['#options']);

    $field_name = $this->fieldDefinition->getName();
    $element['#attributes'] = [
      // Disable core autocomplete.
      'data-jquery-once-autocomplete'         => 'true',
      'data-select2-autocomplete-list-widget' => 'true',
      'class'                                 => ['select2-widget'],
      'data-field-name'                       => $field_name,
    ];
    // Pass an additional data attribute
    // to let select2 JS know whether it should handle input
    // for auto-create or not.
    $settings = $this->getFieldSettings();
    if (isset($settings['handler_settings']['auto_create']) && $settings['handler_settings']['auto_create'] == TRUE) {
      $element['#attributes']['data-auto-create-entity'] = 'enabled';
    }

    // Process the auto-creation when the input data is being gathered.
    $element['#select2'] = [
      'fieldName' => $field_name,
    ] + $settings;
    $element['#value_callback'] = [get_class($this), 'processAutoCreation'];

    $element['#multiple'] = $element['#needs_validation'] = FALSE;
    // Set the additional attribute for limiting
    // the search input visibility if specified.
    $this->limitSearchByMinLength($element['#attributes']);
    // Attach library.
    $element['#attached']['library'][] = 'select2boxes/widget';
    return $element;
  }

}

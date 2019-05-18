<?php

namespace Drupal\straw\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'entity_reference_autocomplete_tags' widget.
 *
 * @FieldWidget(
 *   id = "super_term_reference_autocomplete_widget",
 *   label = @Translation("Autocomplete (Straw style)"),
 *   description = @Translation("An autocomplete text field with term hierarchy support."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class SuperTermReferenceAutocompleteWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $return_element = parent::formElement($items, $delta, $element, $form, $form_state);
    $return_element['target_id']['#type'] = 'super_term_reference_autocomplete';
    return $return_element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return ($field_definition->getSetting('handler') === 'straw');
  }

}

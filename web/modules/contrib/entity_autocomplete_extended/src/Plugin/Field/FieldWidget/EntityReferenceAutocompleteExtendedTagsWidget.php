<?php

namespace Drupal\entity_autocomplete_extended\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of 'entity_reference_autocomplete_extended_tags'.
 *
 * @FieldWidget(
 *   id = "entity_reference_autocomplete_extended_tags",
 *   label = @Translation("Autocomplete Extended (Tags style)"),
 *   description = @Translation("An extended autocomplete text field with tagging support."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class EntityReferenceAutocompleteExtendedTagsWidget extends EntityReferenceAutocompleteExtendedWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['target_id']['#tags'] = TRUE;
    $element['target_id']['#default_value'] = $items->referencedEntities();

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return $values['target_id'];
  }

}

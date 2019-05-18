<?php

namespace Drupal\create_new_entity_reference_permission\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;

/**
 * Plugin implementation of the widget.
 *
 * The 'entity_reference_autocomplete_permissions' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_autocomplete_permissions_widget",
 *   label = @Translation("Autocomplete (with new entity permission)"),
 *   description = @Translation("An autocomplete text field with permissions support."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceAutocompletePermissionsWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if (!\Drupal::currentUser()->hasPermission('create new autocomplete entity reference')) {
      unset($element['target_id']['#autocreate']);
    }

    return $element;
  }

}

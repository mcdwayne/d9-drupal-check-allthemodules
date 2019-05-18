<?php

namespace Drupal\entity_reference_number_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference_number' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_number",
 *   label = @Translation("Entity ID"),
 *   description = @Translation("A number field to enter the entity ID directly."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceNumberWidget extends WidgetBase {

 /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $referenced_entities = $items->referencedEntities();
    $element += [
      '#type' => 'number',
      '#min' => 0,
      // @todo set #max?
      '#default_value' => isset($referenced_entities[$delta]) ? $referenced_entities[$delta]->id() : NULL,
    ];
    // @todo any other custom validation?
    return ['target_id' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      if (empty($value['target_id'])) {
        unset($values[$key]);
      }
    }
    return $values;
  }
}

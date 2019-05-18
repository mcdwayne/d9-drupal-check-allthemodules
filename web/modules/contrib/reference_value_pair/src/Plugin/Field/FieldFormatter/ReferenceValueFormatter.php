<?php

namespace Drupal\reference_value_pair\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Plugin implementation of the 'reference_value_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "reference_value_formatter",
 *   label = @Translation("Reference value formatter"),
 *   field_types = {
 *     "reference_value_pair"
 *   }
 * )
 */
class ReferenceValueFormatter extends ReferenceValueFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    // Include entity and field information to help template suggestions.
    $element = array(
      '#field_name' => $this->fieldDefinition->getName(),
      '#field_type' => $this->fieldDefinition->getType(),
      '#entity_type' => $items->getEntity()->getEntityTypeId(),
      '#bundle' => $items->getEntity()->bundle(),
    );
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $elements[$delta] = array(
        '#theme' => 'reference_value_pair_formatter',
        '#item' => $items[$delta],
        '#entity' => $entity,
        '#label' => $entity ? $entity->label() : $items[$delta]->_label,
        '#element' => $element,
      );
      if ($entity !== NULL) {
        $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

}

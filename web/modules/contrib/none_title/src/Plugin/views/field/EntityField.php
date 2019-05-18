<?php

namespace Drupal\none_title\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityField as EntityFieldOrigin;
use Drupal\views\ResultRow;

/**
 * Class EntityField.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("field")
 *
 * @package Drupal\none_title\Plugin\views\field
 */
class EntityField extends EntityFieldOrigin {

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    $items = parent::getItems($values);
    if (!empty($items)) {
      foreach ($items as $delta => $item) {
        if (!empty($item) && isset($item['raw'])) {
          /** @var \Drupal\Core\Field\Plugin\Field\FieldType\StringItem $raw */
          $raw = $item['raw'];
          /** @var \Drupal\Core\Field\BaseFieldDefinition $field_definition */
          $field_definition = $raw->getFieldDefinition();
          if ($field_definition->getName() == 'title') {
            if ($raw->get('value')->getValue() == '<none>') {
              unset($items[$delta]);
            }
          }
        }
      }
    }
    return $items;
  }

}

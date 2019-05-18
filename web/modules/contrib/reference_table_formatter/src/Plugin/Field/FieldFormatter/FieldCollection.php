<?php

namespace Drupal\reference_table_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\reference_table_formatter\FormatterBase;

/**
 * A field formatter to display a table.
 *
 * @FieldFormatter(
 *   id = "field_collection_table",
 *   label = @Translation("Table of Fields"),
 *   field_types = {
 *     "field_collection"
 *   }
 * )
 */
class FieldCollection extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityIdFromFieldItem(FieldItemInterface $item) {
    return $item->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundleId(FieldDefinitionInterface $field_definition) {
    return $field_definition->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityId(FieldDefinitionInterface $field_definition) {
    return 'field_collection_item';
  }

}

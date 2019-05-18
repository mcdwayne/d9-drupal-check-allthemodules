<?php

namespace Drupal\gridstack\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'GridStack Paragraphs' formatter.
 */
class GridStackParagraphsFormatter extends GridStackEntityFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();

    return $storage->isMultiple() && $storage->getSetting('target_type') === 'paragraph';
  }

}

<?php

namespace Drupal\bitdash_player\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Base class for image file formatters.
 */
abstract class BitdashPlayerFormatterBase extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    return parent::getEntitiesToView($items, $langcode);
  }

}

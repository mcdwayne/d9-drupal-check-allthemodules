<?php

namespace Drupal\term_level\Normalizer;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\term_level\Plugin\Field\FieldType\TermLevelItem;
use Drupal\hal\Normalizer\FieldItemNormalizer;

/**
 * Converts values for TermLevelItem to and from common formats for hal.
 */
class TermLevelItemNormalizer extends FieldItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = TermLevelItem::class;

  /**
   * {@inheritdoc}
   */
  protected function normalizedFieldValues(FieldItemInterface $field_item, $format, array $context) {
    $value = parent::normalizedFieldValues($field_item, $format, $context);
    $value['level'] = $field_item->level;
    return $value;
  }

}

<?php

namespace Drupal\no_nbsp\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;

/**
 * Delete all non-breaking space HTML entities.
 *
 * @Filter(
 *   id = "filter_no_nbsp",
 *   module = "no_nbsp",
 *   title = @Translation("No Non-breaking Space Filter"),
 *   description = @Translation("Delete all non-breaking space HTML entities."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class FilterNoNbsp extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult(_no_nbsp_eraser($text));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $tips = [];
    $tips[] = t('All non-breaking space HTML entities are replaced by blank space characters.');

    if ($long) {
      $tips[] = t('Multiple contiguous space characters are replaced by a single blank space character.');
    }

    return implode(' ', $tips);
  }

}

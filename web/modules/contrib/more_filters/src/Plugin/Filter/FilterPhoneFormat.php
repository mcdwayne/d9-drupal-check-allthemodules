<?php

namespace Drupal\more_filters\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to re-format phone numbers.
 *
 * Finds phone numbers with this format: 555.222.1111
 * and re-formats them into the conventional format: (555) 222-1111.
 *
 * @Filter(
 *   id = "filter_phone_format",
 *   title = @Translation("Convert phone numbers in <code>555.222.1111</code> format to <code>(555) 222-1111</code> format."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class FilterPhoneFormat extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult(_filter_phone_format($text, $this));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('Convert phone numbers in 555.222.1111 format to (555) 222-1111 format.');
    }
    else {
      return $this->t('Convert phone numbers in 555.222.1111 format to (555) 222-1111 format.');
    }
  }

}

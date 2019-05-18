<?php

/**
 * @file
 * Contains \Drupal\joomag_filter\Plugin\Filter\FilterJoomag.
 */

namespace Drupal\joomag_filter\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to display any HTML as plain text.
 *
 * @Filter(
 * id = "filter_joomag",
 * title = @Translation("Joomag filter"),
 * module = "joomag_filter",
 * type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * weight = 100
 * )
 */
class FilterJoomag extends FilterBase {

  /**
   * The actual filtering is performed here.
   * The supplied text should be returned,
   * once any necessary substitutions have taken place.
   */
  public function process($text, $langcode) {
    $jf = new JoomagFilter();

    return new FilterProcessResult($jf->parser($text));
  }

}

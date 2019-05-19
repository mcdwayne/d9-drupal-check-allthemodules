<?php

namespace Drupal\twitter_filters;

/**
 * Class TwitterTextFilters.
 *
 * @package Drupal\twitter_filters
 */
class TwitterTextFilters implements TwitterTextFiltersInterface {

  /**
   * {@inheritdoc}
   */
  public function twitterFilterText($text, $prefix, $destination, $class = '') {
    $match = '/(?<!\w)' . preg_quote($prefix, '/') . '(\w+)/ui';
    if (!empty($class)) {
      $class = " class=\"{$class}\"";
    }
    $replacement = '<a href="' . $destination . '$1"' . $class . '>' . $prefix . '$1</a>';
    return preg_replace($match, $replacement, $text);
  }

}

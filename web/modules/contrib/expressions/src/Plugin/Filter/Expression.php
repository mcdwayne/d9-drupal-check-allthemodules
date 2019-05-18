<?php

/**
 * @file
 * Contains \Drupal\expressions\Plugin\Filter\Expression.
 */

namespace Drupal\expressions\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;

/**
 * @TODO: Write description for the plugin.
 *
 * @Filter(
 *   id = "expression",
 *   title = @Translation("Expression evaluator"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR,
 *   weight = -10
 * )
 */
class Expression extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function prepare($text, $langcode) {
    return preg_replace('#<expression>(.*)</expression>#', '[expression]$1[/expression]', $text);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $text = preg_replace_callback('#\[expression\](.*)\[/expression\]#', [$this, 'evaluate'], $text);
    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    // @TODO: Add filter tips.
    return '';
  }

  /**
   * @param $matches
   */
  protected function evaluate($matches) {
    $language = \Drupal::service('expressions.language');
    $result = $language->evaluate($matches[1]);
    return $result !== NULL ? $result : '{EXPRESSION ERROR}';
  }

}

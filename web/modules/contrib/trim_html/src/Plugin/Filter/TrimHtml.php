<?php

namespace Drupal\trim_html\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a 'Trim HTML' filter.
 *
 * @Filter(
 *   id = "trim_html",
 *   title = @Translation("Trim HTML"),
 *   type = \Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class TrimHtml extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Remove all <br/> and empty (i.e. containing only whitespace or &nbsp;)
    // <p> tags from the end of $text.
    $pattern = '/((\s*<\s*p\s*>(&nbsp;|\s)*<\s*\/p\s*>\s*)|(\s*<\s*br\s*\/\s*>\s*))*$/';
    $text = preg_replace($pattern, '', $text);
    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Trailing html "whitespaces" (e.g. &lt;p>&lt;/p>) are removed.');
  }
}

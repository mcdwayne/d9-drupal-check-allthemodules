<?php

namespace Drupal\bootstrap_utilities\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Component\Utility\Html;

/**
 * Add Bootstrap Class to any blockquote.
 *
 * @Filter(
 *   id = "bootstrap_utilities_blockquote_filter",
 *   title = @Translation("Bootstrap Utilities - Blockquote Classes"),
 *   description = @Translation("This filter will allow you to add default Bootstrap classes to a blockquote"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class BlockquoteFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'blockquote') !== FALSE) {
      $setting_classes = [];
      $setting_classes[] = 'blockquote';

      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);

      $blockquote_elements = $xpath->query('//blockquote');
      if (!is_null($blockquote_elements)) {
        foreach ($blockquote_elements as $element) {
          if ($element->getAttribute('class')) {
            $setting_classes = $element->getAttribute('class');
          }
          $all_classes = implode(' ', $setting_classes);
          $element->setAttribute('class', $all_classes);
        }
      }

      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

}

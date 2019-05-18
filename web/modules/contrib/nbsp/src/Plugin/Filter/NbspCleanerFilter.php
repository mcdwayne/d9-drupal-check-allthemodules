<?php

namespace Drupal\nbsp\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Component\Utility\Html;

/**
 * NBSP Cleaner Filter class. Implements process() method only.
 *
 * @Filter(
 *   id = "nbsp_cleaner_filter",
 *   title = @Translation("Cleanup NBSP markup"),
 *   description = @Translation("Remove <span> tag around <code>&amp;nbsp;</code>."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class NbspCleanerFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if ($filtered = $this->swapNbspHtml($text)) {
      $result = new FilterProcessResult($filtered);
    }
    else {
      $result = new FilterProcessResult($text);
    }

    return $result;
  }

  /**
   * Replace <span class="nbsp"> tags with their respected HTML.
   *
   * @param string $text
   *   The HTML string to replace <span class="nbsp"> tags.
   *
   * @return string
   *   The HTML with all the <span class="nbsp">
   *   tags replaced with their respected html.
   */
  protected function swapNbspHtml($text) {
    $document = Html::load($text);
    $xpath = new \DOMXPath($document);

    foreach ($xpath->query('//span[@class="nbsp"]') as $node) {
      if (!empty($node) && !empty($node->nodeValue)) {
        // PHP DOM removing the tag (not content)
        $node->parentNode->replaceChild(new \DOMText($node->nodeValue), $node);
      }
    }

    return Html::serialize($document);
  }

}

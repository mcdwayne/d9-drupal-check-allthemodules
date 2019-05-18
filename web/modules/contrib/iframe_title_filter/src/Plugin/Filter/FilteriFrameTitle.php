<?php

namespace Drupal\iframe_title_filter\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to provide missing iFrame titles.
 *
 * If an iFrame is attempting to be rendered without a title attribute,
 * attempt to add an appropriate title based on the src URL.
 *
 * @Filter(
 *   id = "filter_iframe_title",
 *   title = @Translation("Add missing titles to iFrames"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   weight = 100
 * )
 */
class FilteriFrameTitle extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Set title attribute for iFrame if it doesn't exist.
    $html_dom = Html::load($text);
    $iframes = $html_dom->getElementsByTagName('iframe');
    foreach ($iframes as $iframe) {
      if (!$iframe->hasAttribute('title')) {
        $url = $iframe->getAttribute('src');
        $url_pieces = parse_url($url);
        $host = $url_pieces['host'];
        $title = "Embedded content from " . $host;
        $iframe->setAttribute('title', $title);
      }
    }

    $text = Html::serialize($html_dom);
    return new FilterProcessResult($text);
  }

}

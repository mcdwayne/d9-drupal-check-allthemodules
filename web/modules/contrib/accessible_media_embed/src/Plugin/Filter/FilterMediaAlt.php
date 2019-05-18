<?php

namespace Drupal\accessible_media_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Render\FilteredMarkup;

/**
 * Provides a filter to caption elements.
 *
 * When used in combination with the filter_caption filter, this must run last.
 *
 * @Filter(
 *   id = "filter_media_alt",
 *   title = @Translation("Context sensitive alt for media"),
 *   description = @Translation("Uses a <code>data-media-alt</code> attribute on <code>&lt;img&gt;</code> tags to add context sensitive alt tags for images."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterMediaAlt extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'data-media-alt') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//*[@data-media-alt-decorative]') as $node) {
        $mediaAltDecorative = $node->getAttribute('data-media-alt-decorative');
        if (!$mediaAltDecorative) {
          continue;
        }

        // Remove the data attributes.
        $node->removeAttribute('data-media-alt-decorative');
        $node->removeAttribute('data-media-alt');

        // Find any images within the media and add an empty 'alt' attribute.
        $images = $xpath->query('//img', $node);
        foreach ($images as $image) {
          $image->setAttribute('alt', "");
        }
      }
      foreach ($xpath->query('//*[@data-media-alt]') as $node) {
        // Read the data-media-alt attribute's value, then delete it.
        $mediaAlt = Html::escape($node->getAttribute('data-media-alt'));

        // Remove the data attributes.
        $node->removeAttribute('data-media-alt-decorative');
        $node->removeAttribute('data-media-alt');

        // Sanitize media alt: decode HTML encoding, remove HTML tags.
        $mediaAlt = Html::decodeEntities($mediaAlt);
        $mediaAlt = FilteredMarkup::create(Xss::filter($mediaAlt));

        // Find any images within the media and add 'alt' attribute.
        $images = $node->getElementsByTagName('img');
        foreach ($images as $image) {
          $image->setAttribute('alt', $mediaAlt);
        }

        // Find any frames within the media and add 'title' attribute.
        $frames = $node->getElementsByTagName('iframe');
        foreach ($frames as $frame) {
          $frame->setAttribute('title', $mediaAlt);
        }
      }
      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

}

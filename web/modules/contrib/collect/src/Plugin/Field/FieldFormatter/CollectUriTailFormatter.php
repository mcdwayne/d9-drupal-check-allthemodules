<?php

/**
 * @file
 * Contains \Drupal\collect\Plugin\Field\FieldFormatter\CollectUriTailFormatter.
 */

namespace Drupal\collect\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Implementation of the 'uri' formatter for Collect URIs.
 *
 * @FieldFormatter(
 *   id = "collect_uri",
 *   label = @Translation("Collect URI Formatter"),
 *   field_types = {
 *     "uri",
 *   }
 * )
 */
class CollectUriTailFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $collect_uri = $item->value;
      if (preg_match('@http://schema.md-systems.ch/collect/[^/]+/(.+)?@', $collect_uri, $matches) == 1) {
        $collect_uri = SafeMarkup::format('<span title="@title">collect:@path</span>', [
          '@title' => $matches[0],
          '@path' => $matches[1]
        ]);
      }
      $elements[$delta]['origin_uri'] = array(
        '#markup' => $collect_uri,
      );
    }

    return $elements;
  }

}

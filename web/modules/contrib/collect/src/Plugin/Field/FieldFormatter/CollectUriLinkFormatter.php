<?php

/**
 * @file
 * Contains \Drupal\collect\Plugin\Field\FieldFormatter\CollectUriLinkFormatter.
 */

namespace Drupal\collect\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\UriLinkFormatter;
use Drupal\Core\Url;

/**
 * Implementation of the 'uri_link' formatter.
 *
 * @FieldFormatter(
 *   id = "collect_uri_link",
 *   label = @Translation("Link to URI if it is URL"),
 *   field_types = {
 *     "uri",
 *   }
 * )
 */
class CollectUriLinkFormatter extends UriLinkFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if (!$item->isEmpty()) {
        $elements[$delta] = [
          '#type' => 'item',
          '#markup' => SafeMarkup::checkPlain($item->value),
        ];
        // Render valid URLs as links.
        if (UrlHelper::isValid($item->value, TRUE)) {
          $elements[$delta] = array(
            '#type' => 'link',
            '#url' => Url::fromUri($item->value),
            '#title' => $item->value,
          );
        }
      }
    }

    return $elements;
  }

}

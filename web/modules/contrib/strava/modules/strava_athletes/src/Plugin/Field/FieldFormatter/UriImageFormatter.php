<?php

namespace Drupal\strava_athletes\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\UriLinkFormatter;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'uri_link' formatter.
 *
 * @FieldFormatter(
 *   id = "uri_image",
 *   label = @Translation("Image from URI"),
 *   field_types = {
 *     "uri",
 *   }
 * )
 */
class UriImageFormatter extends UriLinkFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (!$item->isEmpty()) {
        $elements[$delta] = [
          '#type' => 'markup',
          '#markup' => '<img src="' . Url::fromUri($item->value)->toString() . '" alt="' . Url::fromUri($item->value)->toString() . '"/>',
        ];
      }
    }

    return $elements;
  }

}

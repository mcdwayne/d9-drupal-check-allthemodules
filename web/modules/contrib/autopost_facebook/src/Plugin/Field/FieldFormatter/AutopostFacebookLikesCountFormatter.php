<?php

namespace Drupal\autopost_facebook\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'Likes count' formatter.
 *
 * @FieldFormatter(
 *   id = "autopost_facebook_likes_count",
 *   label = @Translation("Likes count"),
 *   field_types = {
 *     "autopost_facebook"
 *   }
 * )
 */
class AutopostFacebookLikesCountFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      // Render each element as markup.
      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->value,
      ];
    }

    return $element;
  }

}

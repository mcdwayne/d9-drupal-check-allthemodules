<?php

namespace Drupal\entity_slug\Plugin\Field\FieldFormatter;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'slug' formatter.
 *
 * @FieldFormatter(
 *   id = "slug_default",
 *   module = "slug_field",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "slug",
 *     "slug_path",
 *   }
 * )
 */
class SlugFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->value,
      ];
    }

    return $element;
  }
}

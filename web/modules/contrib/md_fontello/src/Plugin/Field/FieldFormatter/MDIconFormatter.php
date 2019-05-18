<?php

namespace Drupal\md_fontello\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * @FieldFormatter(
 *   id = "md_icon",
 *   label = @Translation("Fontello Icon"),
 *   field_types = {
 *     "md_icon"
 *   }
 * )
 */

class MDIconFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $packets = $items->getSetting('packages');

    foreach ($packets as $font => $status) {
      if ($status !== 0) {
        $elements['#attached']['library'][] = 'md_fontello/md_fontello.' . $font;
      }
    }

    foreach ($items as $delta => $item) {
      $icon = nl2br(Html::escape($item->value));
      $elements[$delta] = [
        '#theme' => 'md_icon',
        '#name' => NULL,
        '#icon' => $icon
      ];
    }
    return $elements;
  }

}

<?php

namespace Drupal\micon\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'string_micon' formatter.
 *
 * @FieldFormatter(
 *   id = "string_micon",
 *   label = @Translation("Icon"),
 *   field_types = {
 *     "string_micon"
 *   }
 * )
 */
class StringMiconFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if ($icon = $this->viewIcon($item)) {
        $elements[$delta] = $icon->toRenderable();
      }
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return \Drupal\micon\MiconIcon|null
   *   The Micon icon matching the icon_id.
   */
  protected function viewIcon(FieldItemInterface $item) {
    $icon_id = nl2br(Html::escape($item->value));
    return \Drupal::service('micon.icon.manager')->getIconMatch($icon_id);
  }

}

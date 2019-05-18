<?php

namespace Drupal\font_field_icon\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'FontFieldIconDefaultFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "FontFieldIconDefaultFormatter",
 *   label = @Translation("Show icon and link"),
 *   field_types = {
 *     "font_field_icon"
 *   }
 * )
 */
class FontFieldIconDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];
    foreach ($items as $delta => $item) {
      if ($item->font_field_icon == 'envelope') {
        $href_link = "mailto:" . $item->font_field_icon_link;
        $target_open = "";
      }
      else {
        $href_link = $item->font_field_icon_link;
        $target_open = "target='_blank'";
      }
      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => "<i class='fa fa-{$item->font_field_icon}'></i><a class='icon_field_with_title' href='{$href_link}' {$target_open}>" . $item->font_field_icon_link . "</a>",
      ];
      $elements[$delta]['#attached']['library'][] = 'font_field_icon/font_field_icon';
    }
    return $elements;
  }

}

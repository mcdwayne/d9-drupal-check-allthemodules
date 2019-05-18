<?php

namespace Drupal\font_field_icon\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'FontFieldIconShowFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "FontFieldIconShowFormatter",
 *   label = @Translation("Show icon only"),
 *   field_types = {
 *     "font_field_icon"
 *   }
 * )
 */
class FontFieldIconShowFormatter extends FormatterBase {

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
        '#markup' => "<a class='icon_field_link_alone' href='{$href_link}' {$target_open}><i class='fa fa-{$item->font_field_icon}'></i></a>",
      ];
      $elements[$delta]['#attached']['library'][] = 'font_field_icon/font_field_icon';
    }
    return $elements;
  }

}

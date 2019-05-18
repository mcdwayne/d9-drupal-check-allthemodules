<?php

/**
 * @file
 * Contains Drupal\itchio_field\Plugin\Field\FieldFormatter\ItchioIframeFormatter.
 */

namespace Drupal\itchio_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'itchio_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "itchio_formatter",
 *   module = "itchio_field",
 *   label = @Translation("Simple text-based formatter"),
 *   field_types = {
 *     "itchio_field_itchio"
 *   }
 * )
 */
class ItchioIframeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // Get default values, if set, from the settings form
    $defaults = \Drupal::config('itchio_field.settings');

    foreach ($items as $delta => $item) {

      $use_button = isset($item->use_button) ? $item->use_button : FALSE;

      if (!$use_button) {
        // Generate an iframe
        $default_width = $defaults->get('default_width');
        $default_width = !empty($default_width) ? $default_width : 550;
        $default_height = $defaults->get('default_height');
        $default_height = !empty($default_height) ? $default_height : 165;

        $value = !empty($item->value) ? $item->value : '';
        $linkback = isset($item->linkback) ? $item->linkback : $defaults->get('default_linkback');
        $borderwidth = !empty($item->borderwidth) ? $item->borderwidth : $defaults->get('default_borderwidth');
        $bg_color = !empty($item->bg_color) ? $item->bg_color : $defaults->get('default_bg_color');
        $fg_color = !empty($item->fg_color) ? $item->fg_color : $defaults->get('default_fg_color');
        $link_color = !empty($item->link_color) ? $item->link_color : $defaults->get('default_link_color');
        $border_color = !empty($item->border_color) ? $item->border_color : $defaults->get('default_border_color');
        // Uses the default itch.io iframe width and height if not previously set
        $width = !empty($item->width) ? $item->width : $default_width;
        $height = !empty($item->height) ? $item->height : $default_height;

        $src = 'https://itch.io/embed/' . $value;
        $src_vals = [];
        if (!empty($linkback)) {
          $src_vals[] = 'linkback=true';
        }
        if (!empty($borderwidth)) {
          $src_vals[] = 'border_width=' . $borderwidth;
        }
        if (!empty($bg_color)) {
          $src_vals[] = 'bg_color=' . $bg_color;
        }
        if (!empty($fg_color)) {
          $src_vals[] = 'fg_color=' . $fg_color;
        }
        if (!empty($border_color)) {
          $src_vals[] = 'border_color=' . $border_color;
        }
        if (!empty($link_color)) {
          $src_vals[] = 'link_color=' . $link_color;
        }

        if (!empty($src_vals)) {
          $src .= '?' . implode('&', $src_vals);
        }


        $elements[$delta] = [
          '#type' => 'html_tag',
          '#tag' => 'iframe',
          '#attributes' => [
            'src' => $src,
            'width' => $width,
            'height' => $height,
            'frameborder' => 0
          ],
          '#value' => '',
        ];

      } else {
        // Generate a button

        $button_text = !empty($item->button_text) ? $item->button_text : '';
        $button_user = !empty($item->button_user) ? $item->button_user : $defaults->get('default_username');
        $button_project = !empty($item->button_project) ? $item->button_project : '';

        $elements[$delta][0] = [
          '#type' => 'button',
          '#value' => $button_text,
          '#attributes' => [
            'id' => ['itch-button-' . $button_project]
          ]
        ];
        $elements[$delta][1] = [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => [
            'type' => 'text/javascript',
            'src' => 'https://static.itch.io/api.js',
          ],
          '#value' => '',
        ];
        $elements[$delta][2] = [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => [
            'type' => 'text/javascript',
          ],
          '#value' => 'Itch.attachBuyButton(document.getElementById("itch-button-' . $button_project . '"), { user: "' . $button_user . '",game: "' . $button_project . '"});',
        ];
      }


    }

    return $elements;
  }

}

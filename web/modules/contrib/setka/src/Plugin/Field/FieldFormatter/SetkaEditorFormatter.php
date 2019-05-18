<?php

namespace Drupal\setka_editor\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'setka_editor' formatter.
 *
 * @FieldFormatter(
 *   id = "setka_editor",
 *   module = "setka_editor",
 *   label = @Translation("Setka Editor"),
 *   field_types = {
 *     "text_long",
 *     "string_long",
 *     "text_with_summary",
 *   }
 * )
 */
class SetkaEditorFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $itemValue = $item->value;
      $setkaFormat = FALSE;
      if ($decodedValue = json_decode($itemValue)) {
        if (!empty($decodedValue->postTheme)
          && !empty($decodedValue->postGrid)
          && !empty($decodedValue->postHtml)) {
          $setkaFormat = TRUE;
          $elements[$delta] = [
            '#theme' => 'setka_editor_formatter',
            '#editor_content' => $decodedValue->postHtml,
            '#attached' => [
              'library' => [
                'setka_editor/setka-styles',
                'setka_editor/setka-public-js',
              ],
            ],
          ];
          if (!empty($decodedValue->postTypeKit)) {
            $elements[$delta]['#typekit_id'] = $decodedValue->postTypeKit;
          }
        }
      }
      if (!$setkaFormat) {
        $elements[$delta] = [
          '#type' => 'processed_text',
          '#text' => $item->value,
          '#format' => $item->format,
          '#langcode' => $item->getLangcode(),
        ];
      }
    }

    return $elements;
  }

}

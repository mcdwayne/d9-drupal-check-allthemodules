<?php

namespace Drupal\bitdash_player\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'file_table' formatter.
 *
 * @FieldFormatter(
 *   id = "bitdash_player",
 *   label = @Translation("Bitdah Player"),
 *   field_types = {
 *     "bitdash_player"
 *   }
 * )
 */
class BitdashPlayerFormatter extends BitdashPlayerFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if ($files = $this->getEntitiesToView($items, $langcode)) {
      $header = [t('Attachment'), t('Size')];
      $rows = [];
      foreach ($files as $delta => $file) {
        $rows[] = [
          [
            'data' => [
              '#theme' => 'file_link',
              '#file' => $file,
              '#cache' => [
                'tags' => $file->getCacheTags(),
              ],
            ],
          ],
          ['data' => format_size($file->getSize())],
        ];
      }

      $elements[0] = [];
      if (!empty($rows)) {
        $elements[0] = [
          '#theme' => 'table__file_formatter_table',
          '#header' => $header,
          '#rows' => $rows,
        ];
      }
    }

    return $elements;
  }

}

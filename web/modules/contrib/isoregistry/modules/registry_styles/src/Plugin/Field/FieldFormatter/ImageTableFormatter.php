<?php

namespace Drupal\registry_styles\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\DescriptionAwareFileFormatterBase;

/**
 * Plugin implementation of the 'file_table' formatter.
 *
 * @FieldFormatter(
 *   id = "file_image_table",
 *   label = @Translation("Table of SVG files"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class ImageTableFormatter extends DescriptionAwareFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if ($files = $this->getEntitiesToView($items, $langcode)) {
      $header = [t('Attachment'),t('Preview'), t('Size')];
      $rows = [];
      foreach ($files as $delta => $file) {
        $path = $file->getFileUri();
        $realPath = \Drupal\Core\Url::fromUri(file_create_url($path))->toString();
        
        $item = $file->_referringItem;
        $rows[] = [
          [
            'data' => [
              '#theme' => 'file_link',
              '#file' => $file,
              '#description' => $this->getSetting('use_description_as_link_text') ? $item->description : NULL,
              '#cache' => [
                'tags' => $file->getCacheTags(),
              ],
            ],
          ],
          ['data' => [
            '#theme' => 'file_preview',
            '#filepath' => $realPath,
            ]
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

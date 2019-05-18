<?php

namespace Drupal\ajax_file_download_counter\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'ajax download counter' formatter.
 *
 * @FieldFormatter(
 *   id = "ajax_download_counter",
 *   label = @Translation("Ajax Download Counter"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class AjaxDownloadCounterFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;
      $elements[$delta] = [
        '#theme' => 'file_download_counter',
        '#file' => $file,
        '#attached' => [
          'library' => [
            'ajax_file_download_counter/ajax_dlcount.file',
          ],
        ],
        '#description' => $item->description,
        '#cache' => [
          'max-age' => 0,
        ],
      ];
      // Pass field item attributes to the theme function.
      if (isset($item->_attributes)) {
        $elements[$delta] += ['#attributes' => []];
        $elements[$delta]['#attributes'] += $item->_attributes;
        //kint($elements[$delta]['#attributes']);
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }
    return $elements;
  }
}

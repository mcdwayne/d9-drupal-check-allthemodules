<?php

/**
 * @file
 * Contains \Drupal\epub\Plugin\Field\FieldFormatter\EpubReaderJs.
 */

namespace Drupal\epub\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'epub' formatter.
 *
 * @FieldFormatter(
 *   id = "epub_reader_js",
 *   label = @Translation("Epub: Link to Epub Reader"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class EpubReaderJs extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    global $base_url;
    $elements = array();
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      if ($file->getMimeType() == 'application/epub+zip') {
        $file_url = file_create_url($file->getFileUri());
        $reader = $base_url . '/libraries/epub.js/reader/index.html';
        $elements[$delta] = array(
            '#theme' => 'epub_formatter_reader',
            '#file' => $reader . '?' . $file_url,
        );
      }
      else {
        $elements[$delta] = array (
            '#theme' => 'file_link',
            '#file' => $file,
        );
      }
    }

    return $elements;
  }

}



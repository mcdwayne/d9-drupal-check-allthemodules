<?php

/**
 * @file
 * Contains \Drupal\epub\Plugin\Field\FieldFormatter\EpubTableOfContent.
 */

namespace Drupal\epub\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'epub' formatter.
 *
 * @FieldFormatter(
 *   id = "epub_toc",
 *   label = @Translation("Epub: Table of content"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class EpubTableOfContent extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      if ($file->getMimeType() == 'application/epub+zip') {
        $file_url = file_create_url($file->getFileUri());
        $dir = 'public://epub_content/' . $file->id();
        $elements[$delta] = array(
            '#theme' => 'epub_formatter_toc',
            '#file' => $file_url,
            '#toc' => epub_get_toc($dir, '_blank'),
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
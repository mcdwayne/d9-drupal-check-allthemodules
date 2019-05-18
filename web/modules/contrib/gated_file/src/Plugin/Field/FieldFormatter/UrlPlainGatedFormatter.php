<?php

namespace Drupal\gated_file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'file_url_plain' formatter.
 *
 * @FieldFormatter(
 *   id = "file_url_plain_gated",
 *   label = @Translation("Gated URL to file"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class UrlPlainGatedFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {

      // @todo inject the entityquery
      $query = \Drupal::entityQuery('gated_file')
        ->condition('fid', $file->id());

      if ($gatedFileId = $query->execute()) {
        $gatedFileId = array_pop($gatedFileId);
        $gatedFile = \Drupal::entityTypeManager()->getStorage('gated_file')->load($gatedFileId);
        // Replace the file's link with the Form's link.
        $url = $gatedFile->toUrl();
        $link = $url->toString();
      }
      else {
        $link = file_url_transform_relative(file_create_url($file->getFileUri()));
      }


      $elements[$delta] = [
        '#markup' => $link,
        '#attached' => [
          'library' => ['gated_file/gated-file'],
        ],
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];
    }

    return $elements;
  }

}

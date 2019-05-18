<?php

namespace Drupal\s3fs_cors\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;

/**
 * Plugin implementation of the 'file_default' formatter for s3fs cors files.
 *
 * @FieldFormatter(
 *   id = "s3fs_cors_file_default",
 *   label = @Translation("Generic s3fs cors file"),
 *   field_types = {
 *     "s3fs_cors_file"
 *   }
 * )
 */
class S3fsCorsGenericFileFormatter extends GenericFileFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    return parent::viewElements($items, $langcode);
  }

}

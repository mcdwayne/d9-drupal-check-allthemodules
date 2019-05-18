<?php

namespace Drupal\s3fs_cors\Plugin\Field\FieldType;

use Drupal\Component\Utility\Bytes;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Plugin implementation of the 'file' field type.
 *
 * @FieldType(
 *   id = "s3fs_cors_file",
 *   label = @Translation("S3fs Cors File"),
 *   description = @Translation("This field stores the ID of a file as an integer value."),
 *   category = @Translation("Reference"),
 *   default_widget = "s3fs_cors_file_widget",
 *   default_formatter = "s3fs_cors_file_default",
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class S3fsCorsFileItem extends FileItem {

  /**
   * Retrieves the upload validators for a file field.
   *
   * @return array
   *   An array suitable for passing to file_save_upload() or the file field
   *   element's '#upload_validators' property.
   */
  public function getUploadValidators() {
    $validators = [];
    $settings = $this->getSettings();

    // Special size limit applies to S3 cors files.
    // This is currently 5 GB until the AWS S3 multipart upload functionality
    // is implemented.
    if ($settings['uri_scheme'] == 's3') {
      $max_filesize = Bytes::toInt('5 GB');
    }
    else {
      // Cap the upload size according to the PHP limit.
      $max_filesize = Bytes::toInt(file_upload_max_size());
      if (!empty($settings['max_filesize'])) {
        $max_filesize = min($max_filesize, Bytes::toInt($settings['max_filesize']));
      }
    }

    // There is always a file size limit.
    $validators['file_validate_size'] = [$max_filesize];

    // Add the extension check if necessary.
    if (!empty($settings['file_extensions'])) {
      $validators['file_validate_extensions'] = [$settings['file_extensions']];
    }

    return $validators;
  }

}

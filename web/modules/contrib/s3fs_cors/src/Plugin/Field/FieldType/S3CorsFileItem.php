<?php


namespace Drupal\s3fs_cors\Plugin\Field\FieldType;

use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Plugin implementation of the 's3cors_file' field type - Deprecated.
 *
 * @FieldType(
 *   id = "s3cors_file",
 *   label = @Translation("S3 Cors File [deprecated]"),
 *   description = @Translation("This field stores the ID of a file as an integer value."),
 *   category = @Translation("Reference"),
 *   default_widget = "s3fs_cors_file_widget",
 *   default_formatter = "s3fs_cors_file_default",
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class S3CorsFileItem extends FileItem {

}

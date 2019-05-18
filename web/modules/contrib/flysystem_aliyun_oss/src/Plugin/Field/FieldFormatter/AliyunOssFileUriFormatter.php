<?php

namespace Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\FileUriFormatter;

/**
 * Class AliyunOssFileUriFormatter.
 *
 * @package Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter
 */
class AliyunOssFileUriFormatter extends FileUriFormatter {
  use AliyunOssFieldFormatterTrait;

}

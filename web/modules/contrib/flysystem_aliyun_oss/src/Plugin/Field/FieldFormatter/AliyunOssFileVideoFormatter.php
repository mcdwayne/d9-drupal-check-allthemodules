<?php

namespace Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\FileVideoFormatter;

/**
 * Class AliyunOssFileVideoFormatter.
 *
 * @package Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter
 */
class AliyunOssFileVideoFormatter extends FileVideoFormatter {
  use AliyunOssFieldFormatterTrait;

}

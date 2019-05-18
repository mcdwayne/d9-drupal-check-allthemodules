<?php

namespace Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\TableFormatter;

/**
 * Class AliyunOssFileTableFormatter.
 *
 * @package Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter
 */
class AliyunOssFileTableFormatter extends TableFormatter {
  use AliyunOssFieldFormatterTrait;

}

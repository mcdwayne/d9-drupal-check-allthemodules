<?php

namespace Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\UrlPlainFormatter;

/**
 * Class AliyunOssFileUrlPlainFormatter.
 *
 * @package Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter
 */
class AliyunOssFileUrlPlainFormatter extends UrlPlainFormatter {
  use AliyunOssFieldFormatterTrait;

}

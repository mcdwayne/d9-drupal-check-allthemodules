<?php

namespace Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;

/**
 * Class AliyunOssFileDefaultFormatter.
 *
 * @package Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter
 */
class AliyunOssFileDefaultFormatter extends GenericFileFormatter {
  use AliyunOssFieldFormatterTrait;

}

<?php

namespace Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\DefaultFileFormatter;

/**
 * Class AliyunOssFileLinkFormatter.
 *
 * @package Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter
 */
class AliyunOssFileLinkFormatter extends DefaultFileFormatter {
  use AliyunOssFieldFormatterTrait;

}

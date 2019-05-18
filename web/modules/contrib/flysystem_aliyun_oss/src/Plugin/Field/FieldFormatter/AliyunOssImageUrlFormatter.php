<?php

namespace Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter;

use Drupal\image\Plugin\Field\FieldFormatter\ImageUrlFormatter;

/**
 * Class AliyunOssImageUrlFormatter.
 *
 * @package Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter
 */
class AliyunOssImageUrlFormatter extends ImageUrlFormatter {
  use AliyunOssFieldFormatterTrait;

}

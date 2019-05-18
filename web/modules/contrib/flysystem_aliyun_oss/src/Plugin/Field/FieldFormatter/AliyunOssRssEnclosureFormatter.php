<?php

namespace Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\RSSEnclosureFormatter;

/**
 * Class AliyunOssRssEnclosureFormatter.
 *
 * @package Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter
 */
class AliyunOssRssEnclosureFormatter extends RSSEnclosureFormatter {
  use AliyunOssFieldFormatterTrait;

}

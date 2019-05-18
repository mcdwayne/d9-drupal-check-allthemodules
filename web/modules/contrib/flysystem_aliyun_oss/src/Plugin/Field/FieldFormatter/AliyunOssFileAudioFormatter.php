<?php

namespace Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\FileAudioFormatter;

/**
 * Class AliyunOssFileAudioFormatter.
 *
 * @package Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter
 */
class AliyunOssFileAudioFormatter extends FileAudioFormatter {
  use AliyunOssFieldFormatterTrait;

}

<?php

namespace Drupal\akamai\Helper;

/**
 * Provides formatting to help cache tags conform Akamai requirements.
 *
 * @see https://developer.akamai.com/api/purge/ccu/overview.html
 */
class CacheTagFormatter {

  const RESTRICTED_CHARS_PATTERN = '/[\s\*\"\(\)\,\:\;\<\=\>\?\@\\\[\]\{\}]/';

  /**
   * Format tag according to Akamai guidelines.
   *
   * @param string $tag
   *   A cache tag string.
   *
   * @return string
   *   A compliant cache tag string.
   */
  public function format($tag) {
    $tag = (string) $tag;
    $tag = preg_replace(self::RESTRICTED_CHARS_PATTERN, '_', $tag);
    return $tag;
  }

}

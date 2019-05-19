<?php

namespace Drupal\svg_sanitizer;


use enshrined\svgSanitize\data\AllowedTags;
use enshrined\svgSanitize\data\TagInterface;

class SvgSanitizerTags implements TagInterface {
  /**
   * Tags.
   *
   * @var array
   */
  protected static $tags = [];

  /**
   * Returns an array of tags
   *
   * @return array
   */
  public static function getTags() {
    $allowed = AllowedTags::getTags();

    foreach (self::$tags as $tag) {
      array_push($allowed, $tag);
    }

    return $allowed;
  }

  /**
   * Sets tags.
   *
   * @param string $tagsAsString
   *   Tags, separated by comma.
   */
  public static function setTags($tagsAsString) {
    self::$tags = array_map('trim', explode(',', $tagsAsString));
  }

}

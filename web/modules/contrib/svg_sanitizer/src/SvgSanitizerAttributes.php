<?php

namespace Drupal\svg_sanitizer;

use enshrined\svgSanitize\data\AllowedAttributes;
use enshrined\svgSanitize\data\AttributeInterface;

/**
 * Class SvgSanitizerAttributes
 *
 * Add custom attributes to the list of allowed attributes.
 */
class SvgSanitizerAttributes implements AttributeInterface {

  /**
   * Attributes.
   *
   * @var array
   */
  protected static $attributes = [];

  /**
   * Sets attributes.
   *
   * @param string $attributesAsString
   *   Attributes, separated by comma.
   */
  public static function setAttributes($attributesAsString) {
    self::$attributes = array_map('trim', explode(',', $attributesAsString));
  }

  /**
   * {@inheritdoc}
   */
  public static function getAttributes() {
    $allowed = AllowedAttributes::getAttributes();

    foreach (self::$attributes as $attribute) {
      array_push($allowed, $attribute);
    }

    return $allowed;
  }

}

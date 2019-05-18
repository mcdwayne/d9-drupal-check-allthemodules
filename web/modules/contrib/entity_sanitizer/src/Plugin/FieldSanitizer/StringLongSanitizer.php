<?php

namespace Drupal\entity_sanitizer\Plugin\FieldSanitizer;

/**
 * Handles sanitizing for the string_long field types.
 *
 * @package Drupal\entity_sanitizer\Plugin\FieldSanitizer
 *
 * @FieldSanitizer(
 *   id = "string_long",
 *   label = @Translation("Sanitizer for string_long type fields")
 * )
 */
class StringLongSanitizer extends StringSanitizer {}

<?php

namespace Drupal\entity_sanitizer\Plugin\FieldSanitizer;

/**
 * Handles sanitizing for the text_long field types.
 *
 * @package Drupal\entity_sanitizer\Plugin\FieldSanitizer
 *
 * @FieldSanitizer(
 *   id = "text_long",
 *   label = @Translation("Sanitizer for text_long type fields")
 * )
 */
class TextLongSanitizer extends StringSanitizer {}

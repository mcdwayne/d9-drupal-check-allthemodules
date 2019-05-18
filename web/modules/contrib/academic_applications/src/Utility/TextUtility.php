<?php

namespace Drupal\academic_applications\Utility;

/**
 * Utilities for manipulating text.
 */
class TextUtility {

  /**
   * Converts Drupal field text to a PDF-appropriate encoding.
   *
   * @param string $text
   *   Drupal field API text.
   *
   * @return string
   *   Text converted for PDF.
   */
  public static function textPdfEncode($text) {
    return iconv('UTF-8', 'windows-1252', $text);
  }

}

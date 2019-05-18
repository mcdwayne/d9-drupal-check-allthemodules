<?php

/**
 * @file
 * Contains \Drupal\persiantools\RTLMaker.
 */

namespace Drupal\persiantools;

use \Drupal\Component\Utility\Unicode;

class RTLMaker {

  // Statment direction
  const RTL = 0;
  const LTR = 1;

  // Persian digits
  public static $FA_DIGITS = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');

  // Characters type
  const UN = 0; // Unknown
  const FA = 1;
  const EN = 2;
  const OPENING = 3;
  const CLOSING = 4;
  const DIGIT = 5;
  const EOS = 6; // End Of Statment
  const SLASH = 7;
  const WS = 8; // White Space

  static $OPENING_SYMS = array('(', '{', '[', '"', '\'');
  static $STATMENT_END = array('.', '!', ';', '?', ':');

  /**
   * Main function for multiple features and fixes of persiantools module.
   */
  static function convert_sm($str, $digit_method, $rtl_ltr_fix) {
    $is_all_en = TRUE; $any_en = FALSE;
    $closing_ch = '\0'; $paren_state = 0;
    $dir = RTLMaker::RTL;
    $len = Unicode::strlen($str);
    for ($i = 0; $i < $len; $i = $i + 1) {
      $ch = Unicode::substr($str, $i, 1);
      // Skip unicode characters, which might be mistaken for english characters
      // (e.g. &nbsp;).
      if ($ch == '&') {
        for ($j = $i; $j < $len; $j = $j + 1) {
          if (Unicode::substr($str, $j, 1) == ';') {
            $i = $j;
            continue 2;
          }
          // Longest unicode characater length (which i know about).
          elseif ($j > $i + 9) {
            break;
          }
        }
      }
      $type = RTLMaker::get_char_type($ch);
      if ($type == RTLMaker::FA) {
        $is_all_en = FALSE;
      }
      elseif ($ch == $closing_ch) {
        $type = RTLMaker::CLOSING;
      }
      elseif ($type == RTLMaker::UN) {
        // Last char should go through anyway, to wrap things up.
        if ($i != $len - 1) {
          continue;
        }
      }

      if ($rtl_ltr_fix) {
        list($str, $changed) = RTLMaker::fix_mixed_path($str, $ch, $type, $i, $len);
        if ($changed) {
          $i += 5;
          $len += 5;
        }
      }
      switch ($type) {
        case RTLMaker::DIGIT:
          if ($digit_method == 'full' || ($digit_method == 'smart' && $dir == RTLMaker::RTL && !($is_all_en && $any_en) )) {
            $new_digit = RTLMaker::$FA_DIGITS[$ch - '0'];
            $str = Unicode::substr($str, 0, $i) . $new_digit . Unicode::substr($str, $i + 1);
            $len += Unicode::strlen($new_digit) - 1;
            $i += Unicode::strlen($new_digit) - 1;
          }
          break;

        case RTLMaker::EN:
          $dir = RTLMaker::LTR;
          $any_en = TRUE;
          break;

        case RTLMaker::FA:
          $dir = RTLMaker::RTL;
          break;

        case RTLMaker::OPENING:
          $opening_pos = $i;
          $paren_state = 1;
          break;
      }
      // Fix misplaced enclosing chars, like paranthesis, bracket, quotation, ...
      if ($rtl_ltr_fix) {
        switch ($paren_state) {
          case 1:
            $pre_open = $dir;
            $closing_ch = RTLMaker::get_closing_char($ch);
            $paren_state = 2;
            break;

          case 2:
            if ($type == RTLMaker::CLOSING) {
              // Fix misplaced empty enclosing chars, like function calls, array
              // access, ...
              if ($dir == RTLMaker::LTR) {
                $str = RTLMaker::insert_str($str, '&lrm;', $i + 1);
                $len += 5;
                $i += 5;
              }
              $paren_state = 0;
            }
            elseif ($type == RTLMaker::EN || $type == RTLMaker::FA) {
              $post_open = $dir;
              $paren_state = 3;
            }
            break;

          case 3:
            if ($type == RTLMaker::CLOSING) {
              $pre_close = $dir;
              $paren_state = 4;
              $closed_pos = $i;
            }
            break;

          case 4:
            if ($type == RTLMaker::EN || $type == RTLMaker::FA) {
              $post_close = $dir;
              $paren_state = 5;
            }
            break;
        }
        if ($paren_state == 4 && ($i == $len - 1)) {
          $post_close = RTLMaker::RTL;
          $paren_state = 5;
        }
        if ($paren_state == 5) {
          if ($pre_open == $post_open) {
            $open_dir = $pre_open;
          }
          else {
            $open_dir = RTLMaker::RTL;
          }
          if ($pre_close == $post_close) {
            $close_dir = $pre_close;
          }
          else {
            $close_dir = RTLMaker::RTL;
          }
          if ($open_dir != $close_dir) {
            if ($pre_open == RTLMaker::RTL) {
              $str = RTLMaker::insert_str($str, '&rlm;', $closed_pos);
              $len += 5;
              $i += 5;
            }
            elseif ($pre_open == RTLMaker::LTR) {
              // &#8234; lre (Left to Right Embedding).
              // &#8236: pdf (Pop Directional Formatting).
              $str = RTLMaker::insert_str($str, '&#8234;', $opening_pos);
              $str = RTLMaker::insert_str($str, '&#8236;', $closed_pos + 8);
              $len += 14;
              $i += 14;
            }
            $paren_state = 0;
          }
        }
        // Fix misplaced dot in English Sentences inside RTL direction.
        if ($is_all_en && $dir == RTLMaker::LTR && $type == RTLMaker::EOS) {
          if ($i < $len - 1) {
            $next_ch = Unicode::substr($str, $i + 1, 1);
            $next_type = RTLMaker::get_char_type($next_ch);
            if ($next_type == RTLMaker::EN) {
              continue;
            }
          }
          $str = RTLMaker::insert_str($str, '&lrm;', $i + 1);
          $i += 5;
          $len += 5;
        }
      }
    }
    return $str;
  }

  /**
   * Fix mixed-up paths in rtl blocks.
   * Logic: Gets triggered once a starting '.' or '/' is detected after a whitespace.
   *        The correcting symbol is inserted once an english char is seen inside the path.
   */
  static function fix_mixed_path($str, $ch, $type, $i, $len) {
    static $maybe_path = TRUE;
    static $is_path = FALSE;
    static $path_pos = -1;
    $changed = FALSE;
    if ($i == 0) { $maybe_path = TRUE; }
    if ($type == RTLMaker::WS) {
      $maybe_path = TRUE;
    }
    elseif ($is_path) {
      if ($type == RTLMaker::EN) {
        $str = RTLMaker::insert_str($str, '&lrm;', $path_pos);
        $changed = TRUE;
      }
      $path_pos = -1;
      $is_path = FALSE;
    }
    elseif ($maybe_path) {
      if ($type == RTLMaker::SLASH) {
        $is_path = TRUE;
        if ($path_pos < 0) {
          $path_pos = $i;
        }
      }
      elseif ($ch == '.') {
        if ($path_pos < 0) {
          $path_pos = $i;
        }
      }
      else {
        $maybe_path = FALSE;
        $is_path = FALSE;
        $path_pos = -1;
      }
    }
    // Detect trailing slashes in paths.
    if ($type == RTLMaker::SLASH && $i > 0) {
      $prev_ch = Unicode::substr($str, $i - 1, 1);
      $prev_ch_type = RTLMaker::get_char_type($prev_ch);
      $is_last_char = ($i == $len - 1);
      if ($prev_ch_type == RTLMaker::EN && ($is_last_char || Unicode::substr($str, $i + 1, 1) == ' ')) {
        $str = RTLMaker::insert_str($str, '&lrm;', $i + 1);
        $changed = TRUE;
      }
    }
    return array($str, $changed);
  }

  /**
   * Detects and returns a character's type.
   */
  static function get_char_type($ch) {
    if (($ch >= 'آ' && $ch <= 'ي') || $ch == 'ی') {
      $type = RTLMaker::FA;
    }
    elseif (($ch >= 'a' && $ch <= 'z') || ($ch >= 'A' && $ch <= 'Z')) {
      $type = RTLMaker::EN;
    }
    elseif (in_array($ch, RTLMaker::$OPENING_SYMS)) {
      $type = RTLMaker::OPENING;
    }
    elseif ($ch >= '0' && $ch <= '9') {
      $type = RTLMaker::DIGIT;
    }
    elseif (in_array($ch, RTLMaker::$STATMENT_END)) {
      $type = RTLMaker::EOS;
    }
    elseif ($ch == '/') {
      $type = RTLMaker::SLASH;
    }
    elseif ($ch == ' ' || $ch == '\n') {
      $type = RTLMaker::WS;
    }
    else {
      // Type not detected.
      $type = RTLMaker::UN;
    }
    return $type;
  }

  /**
   * Returns the matching closing char for an opening char.
   */
  static function get_closing_char($char) {
    switch ($char) {
      case '(':
        return ')';
      case '{':
        return '}';
      case '[':
        return ']';
      case '\'':
      case '"':
        return $char;
    }
  }

  /**
   * A simple function to insert a unicode char in a str.
   */
  static function insert_str($str, $char, $pos) {
    return Unicode::substr($str, 0, $pos) . $char . Unicode::substr($str, $pos);
  }
}

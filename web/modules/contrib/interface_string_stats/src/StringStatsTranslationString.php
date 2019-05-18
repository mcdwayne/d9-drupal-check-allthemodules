<?php

namespace Drupal\interface_string_stats;

use Drupal\locale\TranslationString;

/**
 * Defines the locale translation string object.
 *
 * Adds cunt var.
 */
class StringStatsTranslationString extends TranslationString {

  /**
   * The string count.
   *
   * @var int
   */
  public $count;

}

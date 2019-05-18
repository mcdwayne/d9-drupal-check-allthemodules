<?php

namespace Drupal\ad_entity\Helper;

use Drupal\Component\Utility\Crypt;

/**
 * Helper class for handling Html Ids.
 */
abstract class HtmlId {

  /**
   * Stores whether the current request was sent via AJAX.
   *
   * @var bool
   */
  protected static $isAjax;

  /**
   * An array of already seen Ids, including increments.
   *
   * @var array
   */
  protected static $seenIds = [];

  /**
   * Prepares a string for use as a valid Html Id and guarantees uniqueness.
   *
   * @param string $id
   *   The ID to clean.
   *
   * @return string
   *   The cleaned Id.
   */
  public static function getUnique($id) {
    $id = static::get($id);

    if (static::isAjax()) {
      // On Ajax requests, we can only assure uniqueness with random Ids.
      return $id . '--' . Crypt::randomBytesBase64(8);
    }

    if (isset(static::$seenIds[$id])) {
      $id .= '--' . ++static::$seenIds[$id];
    }
    else {
      static::$seenIds[$id] = 1;
    }

    return $id;
  }

  /**
   * Prepares a string for use as a valid HTML ID.
   *
   * @param string $id
   *   The ID to clean.
   *
   * @return string
   *   The cleaned ID.
   *
   * @see self::getUniqueId()
   */
  public static function get($id) {
    $id = str_replace([' ', '[', ']'], ['-', '-', ''], $id);
    $id = preg_replace('/[^A-Za-z0-9\-_]/', '', $id);
    return $id;
  }

  /**
   * Helper method to check if current request is Ajax.
   *
   * @return bool
   *   Returns TRUE when current request is an Ajax request.
   */
  public static function isAjax() {
    if (!isset(static::$isAjax)) {
      static::$isAjax = FALSE;
      if ($request = \Drupal::request()) {
        if ($request->get('_drupal_ajax')) {
          static::$isAjax = TRUE;
        }
      }
    }
    return static::$isAjax;
  }

}

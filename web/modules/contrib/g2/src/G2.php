<?php

/**
 * @file
 * Contains G2.
 *
 * @copyright 2005-2015 Frédéric G. Marand, for Ouest Systemes Informatiques.
 */

namespace Drupal\g2;

/**
 * Class G2 is the container for general-use G2 data.
 */
class G2 {
  /**
   * The key for the module configuration.
   */
  const CONFIG_NAME = 'g2.settings';

  // The name of the node.type.g2_entry config entity.
  const NODE_TYPE = 'g2_entry';

  /**
   * The G2 permission for normal users.
   */
  const PERM_VIEW = 'view g2 entries';

  /**
   * The G2 permission for administrators.
   */
  const PERM_ADMIN = 'administer g2 entries';

  /**
   * The public-facing version: two first levels for semantic versioning.
   */
  const VERSION = '8.1';

  /**
   * The API format.
   */
  const API_VERSION = 8;

  /**
   * Block: alphabar.
   */
  const DELTA_ALPHABAR = 'alphabar';

  /**
   * Block: n most recent.
   */
  const DELTA_LATEST = 'latest';

  /**
   * Block: random.
   */
  const DELTA_RANDOM = 'random';

  /**
   * Block: n most viewed.
   */
  const DELTA_TOP = 'top';

  /**
   * Block: word of the day.
   */
  const DELTA_WOTD = 'wotd';

  /**
   * Encodes terminal path portions for G2.
   *
   * This allows linking to things containing #, + or '.', like 'C++', 'C#' or
   * the '.' initial.
   *
   * Warning: this is NOT a generic replacement for urlencode, but covers a very
   * specific G2-related need.
   *
   * @param string $terminal
   *   The terminal rune to encode.
   *
   * @return string
   *   The encoded terminal.
   */
  public static function encodeTerminal($terminal) {
    $terminal = strtr($terminal, array(
      '.' => '%2E',
      '/' => '%2F',
      '#' => '%23',
      '&' => '%26',
      '+' => '%2B',
    ));
    return $terminal;
  }

  /**
   * Return the RPC API version.
   *
   * @return int
   *   The version of the API format.
   */
  public static function api() {
    return static::API_VERSION;
  }

}

<?php

/**
 * @file
 * Contains the G2 Word of the Day block plugin.
 *
 * @copyright 2005-2015 Frédéric G. Marand, for Ouest Systemes Informatiques.
 */

namespace Drupal\g2\Plugin\Block;

/**
 * Class Wotd is the Word of the Day plugin.
 *
 * @state g2.wotd.date
 * @state g2.wotd.entry
 */
class Wotd {
  /**
   * Default for the current WOTD state entry: none.
   */
  const DEFAULT_ENTRY = 0;

  /**
   * Default G2 WOTD entry, for both block and feed. Translatable.
   *
   * TODO: check whether this needn't be moved to a WOTD service used by both
   * block and feed.
   * TODO: check whether this is not redundant with the plugin title.
   */
  const DEFAULT_TITLE = 'Word of the day in the G2 glossary';

}

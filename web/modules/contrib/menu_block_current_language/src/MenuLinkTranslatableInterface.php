<?php

namespace Drupal\menu_block_current_language;

/**
 * Defines an interface for exposing multilingual capabilities.
 *
 * @package Drupal\menu_block_current_language
 */
interface MenuLinkTranslatableInterface {

  /**
   * Determines if menu link has translation for current langauge.
   *
   * Menu link will be hidden if no translation is found.
   *
   * @param string $langcode
   *   The langcode.
   *
   * @return bool
   *   TRUE if menu link has a translation, FALSE if not.
   */
  public function hasTranslation($langcode);

}

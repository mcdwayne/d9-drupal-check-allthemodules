<?php

namespace Drupal\ultimenu;

/**
 * Interface for Ultimenu tools.
 */
interface UltimenuToolInterface {

  /**
   * Defines constant maz length for the region key.
   */
  const MAX_LENGTH = 28;

  /**
   * Gets the shortened hash of a menu item key.
   *
   * @param string $key
   *   The menu item key.
   *
   * @return string
   *   The shortened hash.
   */
  public function getShortenedHash($key);

  /**
   * Gets the shortened UUID.
   *
   * @param string $key
   *   The menu item key with UUID.
   *
   * @return string
   *   The shortened UUID.
   */
  public function getShortenedUuid($key);

  /**
   * Simplify menu names or menu item titles for region key.
   *
   * If region key is to use menu item title:
   * Region key: ultimenu_LOOOOOOOOOOOONGMENUNAME_LOOOOOOOOOOOOOOOOOONGMENUITEM.
   * If region key is to use unfriendly key UUID, we'll only care for menu name.
   * Region key: ultimenu_LOOOOOOOOOOOOOONGMENUNAME_1c2d3e4.
   *
   * @param string $string
   *   The Menu name or menu item title.
   * @param int $max_length
   *   The amount of characters to truncate.
   *
   * @return string
   *   The truncated menu properties ready to use for region key.
   */
  public function truncateRegionKey($string, $max_length = self::MAX_LENGTH);

  /**
   * Gets the region key.
   *
   * @param object $link
   *   The menu item link object.
   * @param int $max_length
   *   The amount of characters to truncate.
   *
   * @return string
   *   The region key name based on shortened UUID, or menu item title.
   */
  public function getRegionKey($link, $max_length = self::MAX_LENGTH);

  /**
   * Returns the default theme Ultimenu regions from theme .info.yml.
   *
   * @param array $ultimenu_regions
   *   The ultimenu theme regions.
   *
   * @return array
   *   The Ultimenu regions.
   */
  public function parseThemeInfo(array $ultimenu_regions = []);

}

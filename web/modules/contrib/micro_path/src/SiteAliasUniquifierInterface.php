<?php

namespace Drupal\micro_path;

use Drupal\Core\Language\LanguageInterface;

/**
 * Provides an interface for alias uniquifiers.
 */
interface SiteAliasUniquifierInterface {

  /**
   * Check to ensure a path alias is unique per micro site and add suffix variants if necessary.
   *
   * Given an alias 'content/test' if a path alias with the exact alias already
   * exists, the function will change the alias to 'content/test-0' and will
   * increase the number suffix until it finds a unique alias.
   *
   * @param string $alias
   *   A string with the alias. Can be altered by reference.
   * @param string $source
   *   A string with the path source.
   * @param integer $site_id
   *   The micro site id.
   * @param string $langcode
   *   A string with a language code.
   */
  public function uniquify(&$alias, $source, $site_id, $langcode);

  /**
   * Checks if an alias is reserved.
   *
   * @param string $alias
   *   The alias.
   * @param string $source
   *   The source.
   * @param integer $site_id
   *   The micro site id.
   * @param string $langcode
   *   (optional) The language code.
   *
   * @return bool
   *   Returns TRUE if the alias is reserved.
   */
  public function isReserved($alias, $source, $site_id, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED);

}

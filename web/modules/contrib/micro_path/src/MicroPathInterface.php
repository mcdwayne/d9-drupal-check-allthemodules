<?php

namespace Drupal\micro_path;

/**
 * Provides an interface for micro path.
 */
interface MicroPathInterface {

  /**
   * Get source for micro_path.
   *
   * @return string
   *   Returns micro path source.
   */
  public function getSource();

  /**
   * Get alias for micro_path.
   *
   * @return string
   *   Returns micro path alias.
   */
  public function getAlias();

  /**
   * Get language code for micro_path.
   *
   * @return string
   *   Returns micro path language code.
   */
  public function getLanguageCode();

  /**
   * Get micro site id for micro_path.
   *
   * @return string
   *   Returns micro path site id.
   */
  public function getSiteId();

}

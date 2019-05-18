<?php

namespace Drupal\flags\Flags;

/**
 * Defines a common interface for country managers.
 */
interface FlagsManagerInterface {

  /**
   * Returns a list of country code => country name pairs.
   *
   * @return array
   *   An array of country code => country name pairs.
   */
  public function getList();

}

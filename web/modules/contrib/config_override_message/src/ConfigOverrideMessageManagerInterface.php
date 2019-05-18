<?php

namespace Drupal\config_override_message;

/**
 * Config override message manager interface.
 */
interface ConfigOverrideMessageManagerInterface {

  /**
   * Get config overrides.
   *
   * @return array
   *   An associative array of overrides.
   */
  public function getOverrides();

  /**
   * Get config override messages.
   *
   * @return array
   *   An associative array of messages keyed by path.
   */
  public function getMessages();

}

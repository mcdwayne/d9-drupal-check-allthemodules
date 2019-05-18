<?php

namespace Drupal\migrate_social;

/**
 * An interface for all SocialNetwork type plugins.
 */
interface SocialNetworkInterface extends \Iterator, \Countable {

  /**
   * Provide a description of the plugin.
   *
   * @return string
   *   A string description of the plugin.
   */
  public function description();

}

<?php

/**
 * @file
 * Contains \Drupal\ip_ban\IpBanSetBanInterface.
 */

namespace Drupal\ip_ban;

interface IpBanSetBanInterface {
  
   /**
   * Set the user's ban value based on their country or individual IP address.
   * 
   * Load the user's country code based on their IP address, then check if
   * that country is set to complete ban, read-only, or has no access
   * restrictions. We then do the same for any additional read-only or complete
   * ban IP addresses added. If the user matched a country or an IP address
   * entered, then they are shown a message and/or redirected based on complete
   * ban or read-only.
   */
  public function iPBanSetValue();
  
  /**
   * Returns the ban value currently set.
   *
   * @return int
   *   The user's ban value (one of IP_BAN_NOBAN (0), IP_BAN_READONLY (1), 
   *   or IP_BAN_BANNED (2), which are defined in the .module file.
   */
  public function getBanValue();

  /**
   * Determines action based on current user's ban setting.
   *
   * Determine the action based on the user's ban setting. If the user is 
   * anonymous, this will be set via middleware; otherwise it will be set via
   * an event subscriber.
   */
  public function iPBanDetermineAction();
  
}
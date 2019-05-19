<?php

namespace Drupal\pusher_integration;

/**
 * @file
 * Contains \Drupal\captcha\ChannelPathMapInterface.
 */

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface ChannelPathMapInterface.
 *
 * @package Drupal\pusher_integration
 *
 * Provides an interface defining a CaptchaPoint entity.
 */
interface ChannelPathMapInterface extends ConfigEntityInterface {

  /**
   * Getter for machine ID property.
   */
  public function getMapId();

  /**
   * Setter for channelName property.
   *
   * @param string $channelName
   *   Map entry machine ID string.
   */
  public function setChannelName($channelName);

  /**
   * Getter for channelName property.
   *
   * @return string
   *   Label string.
   */
  public function getChannelName();

  /**
   * Setter for pathPattern property.
   *
   * @param string $pathPattern
   *   Label string.
   */
  public function setPathPattern($pathPattern);

  /**
   * Getter for pathPattern property.
   *
   * @return string
   *   Label string.
   */
  public function getPathPattern();

}

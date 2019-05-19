<?php

namespace Drupal\switches;

/**
 * Defines the required interface for the Switch Manager service.
 */
interface SwitchManagerInterface {

  /**
   * Loads a switch instance.
   *
   * @param string $switch_id
   *   The switch machine name.
   *
   * @return \Drupal\switches\Entity\SwitchInterface
   *   The loaded and configured switch entity.
   *
   * @throws \Drupal\switches\Exception\MissingSwitchException
   *   Exception thrown when an undefined Switch is requested.
   */
  public function getSwitch($switch_id);

  /**
   * Returns activation status for switch.
   *
   * @param string $switch_id
   *   The switch machine name.
   *
   * @return bool
   *   The activation status for the specified switch entity.
   */
  public function getActivationStatus($switch_id);

}

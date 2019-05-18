<?php

namespace Drupal\carerix_form\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface CarerixFormInterface.
 *
 * @package Drupal\carerix_form\Entity
 */
interface CarerixFormInterface extends ConfigEntityInterface {

  /**
   * Returns the config entity settings.
   *
   * @return array
   *   Array of settings.
   */
  public function getSettings();

  /**
   * Sets the config entity form settings.
   *
   * @param array $settings
   *   Array of settings.
   *
   * @return \Drupal\carerix_form\Entity\CarerixFormInterface
   *   Returns the config entity.
   */
  public function setSettings(array $settings);

}

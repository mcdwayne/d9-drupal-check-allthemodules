<?php

/**
 * @file
 * Contains \Drupal\cas_server\CasServerServiceInterface.
 */

namespace Drupal\cas_server;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining a CasServerService.
 */
interface CasServerServiceInterface extends ConfigEntityInterface {

  /**
   * Get the machine name.
   */
  public function getId();

  /**
   * Set the machine name.
   *
   * @param string $machine_name
   *   The machine-readable name.
   */
  public function setId($machine_name);

  /**
   * Get the label.
   */
  public function getLabel();

  /**
   * Set the label.
   *
   * @param string $label
   *   The label.
   */
  public function setLabel($label);
  
  /**
   * Get the service definition pattern.
   */
  public function getService();

  /**
   * Set the service definition pattern.
   *
   * @param string $service
   *   A service string pattern.
   */
  public function setService($service);

  /**
   * Get the single sign on status.
   */
  public function getSso();

  /**
   * Set the single sign on status.
   *
   * @param bool $status
   *   Whether or not the service is SSO enabled.
   */
  public function setSso($status);

  /**
   * Get the released attibute names.
   */
  public function getAttributes();

  /**
   * Set the released attributes.
   *
   * @param array $attributes
   *   A list of user field machine names to release as attributes.
   */
  public function setAttributes($attributes);

}

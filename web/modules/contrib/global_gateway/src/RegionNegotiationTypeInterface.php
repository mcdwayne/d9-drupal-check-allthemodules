<?php

namespace Drupal\global_gateway;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface for region negotiation classes.
 */
interface RegionNegotiationTypeInterface extends ConfigurablePluginInterface {

  public function id();

  public function getLabel();

  public function getDescription();

  public function getWeight();

  public function get($key);

  public function set($key, $value);

  public function getConfigRoute();

  /**
   * Injects the current user.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current active user.
   */
  public function setCurrentUser(AccountInterface $current_user);

  /**
   * Performs language negotiation.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   (optional) The current request. Defaults to NULL if it has not been
   *   initialized yet.
   *
   * @return string
   *   A valid language code or FALSE if the negotiation was unsuccessful.
   */
  public function getRegionCode(Request $request = NULL);

  public function persist($region_code);

}

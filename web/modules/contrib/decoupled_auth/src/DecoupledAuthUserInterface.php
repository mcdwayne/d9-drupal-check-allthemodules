<?php

namespace Drupal\decoupled_auth;

use Drupal\user\UserInterface;

/**
 * Provides an interface defining a user entity.
 *
 * @ingroup user_api
 */
interface DecoupledAuthUserInterface extends UserInterface {

  /**
   * Check whether this user is decoupled.
   *
   * @return bool
   *   Whether this user is decoupled.
   */
  public function isDecoupled();

  /**
   * Set this user to the decoupled state.
   *
   * @return DecoupledAuthUserInterface
   *   The user being decoupled.
   */
  public function decouple();

  /**
   * Check whether this user is coupled.
   *
   * @return bool
   *   Whether this user is coupled.
   */
  public function isCoupled();

  /**
   * Set this user to the coupled state.
   *
   * @return static
   */
  public function couple();

  /**
   * Calculate the decoupled state of this user.
   */
  public function calculateDecoupled();

}

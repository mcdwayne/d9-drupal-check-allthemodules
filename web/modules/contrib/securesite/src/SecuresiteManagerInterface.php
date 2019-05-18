<?php

/**
 * @file
 * Contains \Drupal\securesite\SecuresiteManagerInterface.
 */

namespace Drupal\securesite;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Authentication\AuthenticationManager;

/**
 * Defines an interface for managing securesite authentication
 */
interface SecuresiteManagerInterface {


  public function setRequest(Request $request);

  /**
   * Return the appropriate method of authentication for the request
   *
   * @return int
   *    type of the authentication mechanism
   */
  public function getMechanism();

  /**
   * @param int $type
   *    type of the authentication mechanism
   */
  public function boot($type);

  public function showDialog($type);

  public function forcedAuth();
}
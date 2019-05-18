<?php

/**
 * @file
 * Contains \Drupal\hawk_auth\HawkAuthCredentialsViewEvent.
 */

namespace Drupal\hawk_auth;

use Drupal\hawk_auth\Entity\HawkCredentialInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Defines the class for event fired when an user's credentials are viewed.
 */
class HawkAuthCredentialsViewEvent extends GenericEvent {

  /**
   * The user whose credentials are being viewed.
   *
   * @var UserInterface
   */
  protected $user;

  /**
   * An array of credentials currently being displayed
   *
   * @var HawkCredentialInterface[] $credentials
   */
  protected $credentials;

  /**
   * Table build array.
   *
   * @var array
   */
  protected $build;

  /**
   * Constructs a HawkAuthCredentialsViewEvent object.
   *
   * @param UserInterface $user
   *   The user whose credentials are being viewed.
   * @param HawkCredentialInterface[] $credentials
   *   Hawk credentials belonging to the user.
   * @param array $build
   *   The build of the table.
   */
  public function __construct(UserInterface $user, array $credentials, array $build) {
    parent::__construct();
    $this->user = $user;
    $this->credentials = $credentials;
    $this->build = $build;
  }

  /**
   * The user whose credentials are being viewed.
   *
   * @return UserInterface
   *   Drupal user.
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * The hawk credentials.@
   *
   * @return HawkCredentialInterface[]
   *   Array of credentials.
   */
  public function getCredentials() {
    return $this->credentials;
  }

  /**
   * Build of the page.
   *
   * @return array
   *   Page build array.
   */
  public function getBuild() {
    return $this->build;
  }

  /**
   * Sets the build of the page
   *
   * @param array $build
   *   The page build
   *
   * @return void
   */
  public function setBuild(array $build) {
    $this->build = $build;
  }

}
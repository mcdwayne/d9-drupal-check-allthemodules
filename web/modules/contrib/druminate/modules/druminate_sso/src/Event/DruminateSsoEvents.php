<?php

namespace Drupal\druminate_sso\Event;

/**
 * Defines events for Druminate SSO.
 *
 * @package Drupal\druminate_sso\Event
 */
final class DruminateSsoEvents {

  /**
   * Name of the event fired before a Drupal user is logged in.
   *
   * This event allows modules to react on the fact that a user logged in
   * to Drupal, following the authentication with LO SSO.
   *
   * The event listener method receives a
   * \Drupal\druminate_sso\Event\DruminateSSOPreLoginEvent instance.
   *
   * @Event
   *
   * @var string
   */
  const PRE_LOGIN_EVENT = 'druminate_sso.pre_login';

}

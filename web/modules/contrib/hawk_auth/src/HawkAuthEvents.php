<?php

/**
 * @file
 * Contains \Drupal\hawk_auth\HawkAuthEvents.
 */

namespace Drupal\hawk_auth;

/**
 * Contains all events thrown while handling hawk auth module.
 */
final class HawkAuthEvents {

  /**
   * Name of the event triggered when an user's hawk credentials are viewed.
   *
   * The event allows you to modify the table built when an user's credentials
   * are being viewed.
   * The event is passed an instance of
   * \Drupal\hawk_auth\HawkAuthCredentialsViewEvent
   *
   * @Event
   *
   * @see \Drupal\hawk_auth\Controller\HawkAuthController
   *
   * @var string
   */
  const VIEW_CREDENTIALS = 'hawk_auth.view_credentials';

}
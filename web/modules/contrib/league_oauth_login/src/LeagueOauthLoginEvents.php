<?php

namespace Drupal\league_oauth_login;

/**
 * Events for this module.
 */
final class LeagueOauthLoginEvents {

  /**
   * The name of the event.
   *
   * @var string
   *
   * @see \Drupal\league_oauth_login\Controller\LoginController::login()
   */
  const LOGIN_WITH_CODE = 'league_oauth_login.login_with_code';

  /**
   * The name of the event.
   *
   * @var string
   *
   * @see \Drupal\league_oauth_login\Controller\LoginController::login()
   */
  const LOGIN_WHILE_LOGGED_IN = 'league_oauth_login.login_while_logged_in';

  /**
   * An event containing the access token.
   */
  const ACCESS_TOKEN_EVENT = 'league_oauth_login.access_token';

}

<?php

namespace Drupal\webserver_auth;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\user\Entity\User;

class WebserverAuthHelper {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new cookie authentication provider.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   *
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Retrieving remove username from server variables.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return string
   */
  public function getRemoteUser(Request $request) {
    $authname = NULL;

    // Checking if authname is located in one of server vars.
    if ($request->server->get('REDIRECT_REMOTE_USER')) {
      $authname = $request->server->get('REDIRECT_REMOTE_USER');
    }
    elseif ($request->server->get('REMOTE_USER')) {
      $authname = $request->server->get('REMOTE_USER');
    }
    elseif ($request->server->get('PHP_AUTH_USER')) {
      $authname = $request->server->get('PHP_AUTH_USER');
    }

    $config = \Drupal::config('webserver_auth.settings');

    // Stripping NTLM-style prefixes.
    if ($config->get('strip_prefix')) {
      $fields = explode("\\", $authname);
      $authname = end($fields);
    }

    // Strippting domain.
    if ($config->get('strip_domain')) {
      $fields = explode ('@', $authname);
      $authname = $fields[0];
    }

    return $authname;
  }

  /**
   * Checking that user exists in the system.
   * Creating new user if site is configured this way.
   *
   * @param string $authname
   *
   * @return integer
   */
  public function validateRemoteUser($authname) {
    // Checking if user exists and not blocked.
    $query = $this->connection->select('users_field_data', 'u');
    $query->fields('u', array('uid', 'status'));
    $query->condition('u.name', $authname, '=');
    $result = $query->execute();
    $data = $result->fetchAssoc();

    // Creating new user.
    $config = \Drupal::config('webserver_auth.settings');
    if ($authname && $config->get('create_user') && !$data) {
      $new_user = $this->createNewUser($authname);
      return $new_user->id();
    }

    // Letting user know that his account was blocked.
    if ($data && !$data['status']) {
      drupal_set_message(t('You account was blocked.'), 'error');
    }

    if ($data['status']) {
      return $data['uid'];
    }

    return NULL;
  }

  /**
   * Login in user. This is basically copy of user_login_finalize with few small changes.
   *
   * @param $account
   */
  public function logInUser($account) {
    \Drupal::currentUser()->setAccount($account);
    \Drupal::logger('user')
      ->notice('Webserver Auth Session opened for %name.', array('%name' => $account->getUsername()));

    // Update the user table timestamp noting user has logged in.
    // This is also used to invalidate one-time login links.
    $account->setLastLoginTime(REQUEST_TIME);
    \Drupal::entityManager()
      ->getStorage('user')
      ->updateLastLoginTimestamp($account);

    // Regenerate the session ID to prevent against session fixation attacks.
    // This is called before hook_user_login() in case one of those functions
    // fails or incorrectly does a redirect which would leave the old session
    // in place.
    \Drupal::service('session')->migrate();
    \Drupal::service('session')->set('uid', $account->id());
    \Drupal::moduleHandler()->invokeAll('user_login', array($account));
  }

  /**
   * @param $authname
   *
   * @return \Drupal\user\Entity\User $used
   */
  public function createNewUser($authname) {
    // Generating password. It won't be used, but we still don't want
    // to use empty password or same password for all users.
    $pass = user_password(12);

    $data = [
      'name' => $authname,
      'pass' => $pass,
    ];

    $user = User::create($data);
    $user->activate();
    $user->save();

    return $user;
  }

  /**
   * Login in user. This is basically copy of user_logout with few small changes.
   */
  public function logOutUser() {
    $user = \Drupal::currentUser();

    \Drupal::logger('user')->notice('Webserver Auth Session closed for %name.', array('%name' => $user->getAccountName()));

    \Drupal::moduleHandler()->invokeAll('user_logout', array($user));

    // Destroy the current session, and reset $user to the anonymous user.
    // Note: In Symfony the session is intended to be destroyed with
    // Session::invalidate(). Regrettably this method is currently broken and may
    // lead to the creation of spurious session records in the database.
    // @see https://github.com/symfony/symfony/issues/12375
    \Drupal::service('session_manager')->destroy();
    $user->setAccount(new AnonymousUserSession());
  }
}
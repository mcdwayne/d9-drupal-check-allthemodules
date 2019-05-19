<?php

namespace Drupal\webserver_auth\Authentication\Provider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Session\SessionConfigurationInterface;
use Drupal\webserver_auth\WebserverAuthHelper;


/**
 * Cookie based authentication provider.
 */
class WebserverAuth implements AuthenticationProviderInterface {

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfiguration;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Helper class that brings some helper functionality related to webserver authentication.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $helper;

  /**
   * Constructs a new webserver authentication provider.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   *
   * @param \Drupal\webserver_auth\WebserverAuthHelper $helper
   *   Helper class that brings some helper functionality related to webserver authentication.
   */
  public function __construct(SessionConfigurationInterface $session_configuration, Connection $connection, WebserverAuthHelper $helper) {
    $this->sessionConfiguration = $session_configuration;
    $this->connection = $connection;
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    // If our module is enabled, we want this auth provider to
    // be always preferable.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    return $this->getUserFromSession($request->getSession(), $request);
  }

  /**
   * Returns the UserSession object for the given session.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Drupal\Core\Session\AccountInterface|null The UserSession object for the current user, or NULL if this is an
   *   The UserSession object for the current user, or NULL if this is an
   *   anonymous session.
   */
  protected function getUserFromSession(SessionInterface $session, Request $request) {
    // Checking if we got remote user set.
    $authname = $this->helper->getRemoteUser($request);


    // Loging user out if no authname provided, but drupal still keeps user logged in.
    if (!$authname && $session->get('webserver_auth')) {

      // We don't to keep user logged in anymore.
      $session->remove('webserver_auth');
      return NULL;
    }

    // Logging out user if current user differs from new remote user.
    if ($authname && $session->get('webserver_auth') && $authname != $session->get('webserver_auth')) {

      // We seeing new authname came up, so we assuming previous user logged out.
      $session->remove('webserver_auth');
    }

    if (!($uid = $this->helper->validateRemoteUser($authname))) {
      return NULL;
    }

    // Retrieving user data.
    $values = $this->connection->query('SELECT * FROM {users_field_data} u WHERE u.uid = :uid AND u.default_langcode = 1', [':uid' => $uid])
      ->fetchAssoc();

    // Check if the user data was found. We've already validated status by that point.
    if (empty($values)) {
      return NULL;
    }

    // Add the user's roles.
    $rids = $this->connection->query('SELECT roles_target_id FROM {user__roles} WHERE entity_id = :uid', [':uid' => $values['uid']])
      ->fetchCol();

    $values['roles'] = array_merge([AccountInterface::AUTHENTICATED_ROLE], $rids);

    // Setting out webserver variable.
    if (!$session->get('webserver_auth')) {
      $session->set('webserver_auth', $authname);
    }

    $user_session = new UserSession($values);
    return $user_session;
  }
}

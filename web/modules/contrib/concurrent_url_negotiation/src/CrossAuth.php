<?php

namespace Drupal\concurrent_url_negotiation;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class CrossAuth.
 *
 * Service that provides token generation, token authentication/logout and
 * cleanup of the tokens and session bindings.
 */
class CrossAuth {

  /**
   * The amount of seconds a token is valid for.
   */
  const TOKEN_LIFESPAN = 120;

  /**
   * The key in the $_SESSION in which the family id is stored.
   */
  const SESSION_FAMILY_KEY = 'cross_auth.session_family_id';

  /**
   * The amount of seconds a session family stays in database.
   */
  const SESSION_FAMILY_LIFETIME = 2160000;

  /**
   * The request object.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The database connection for queries.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * User storage for finding users.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * The current user logged in.
   *
   * @var null|\Drupal\Core\Session\AccountProxyInterface
   */
  protected $user;

  /**
   * The current session ID hashed.
   *
   * @var string
   */
  protected $sessionHash;

  /**
   * CrossAuth constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *    RequestStack to get current request.
   * @param \Drupal\Core\Database\Connection $database
   *    Database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *    Entity type manager to get the user storage.
   * @param null|\Drupal\Core\Session\AccountProxyInterface $user
   *    Current logged in user.
   */
  public function __construct(RequestStack $requestStack, Connection $database, EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $user = NULL) {
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->database = $database;
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->user = $user;
  }

  /**
   * Generates an authentication token for currently logged in user.
   *
   * @return array|bool
   *    The array containing the token and it's id, or FALSE otherwise.
   */
  public function generateToken() {
    if ($this->user->isAnonymous()) {
      return FALSE;
    }

    // For performance sake don't use the Crypt::hashBase64.
    $token = Crypt::randomBytesBase64(32);
    $authId = $this->database->insert('users_cross_auth')
      ->fields([
        'token' => hash('sha256', $token),
        'user_id' => $this->user->id(),
        'ip' => $this->currentRequest->getClientIp(),
        'session_family_id' => $this->getSessionFamilyId(),
        'created' => time(),
      ])->execute();

    return [
      'id' => $authId,
      'token' => $token,
    ];
  }

  /**
   * Authenticates a user from token and it's id.
   *
   * @param int $id
   *    The id of the token.
   * @param string $token
   *    The token to check.
   *
   * @return bool
   *    Whether the user was successfully logged in.
   */
  public function authenticate($id, $token) {
    if (!isset($id) || !isset($token) || !is_numeric($id)) {
      return FALSE;
    }

    // Retrieve token data on provided ID.
    $authRow = $this->database->select('users_cross_auth')
      ->fields('users_cross_auth')
      ->condition('id', $id)
      ->execute()->fetchAssoc();

    if (empty($authRow)) {
      return FALSE;
    }

    // Prevent expired token from being used. Validate that the IP and the token
    // match.
    if (
      time() - $authRow['created'] < self::TOKEN_LIFESPAN &&
      $authRow['ip'] == $this->currentRequest->getClientIp() &&
      $authRow['token'] == hash('sha256', $token)
    ) {
      $user = $this->userStorage->load($authRow['user_id']);

      if (!empty($user)) {

        // Only log in the user if it isn't already.
        if ($this->user->isAnonymous()) {
          user_login_finalize($user);
        }

        // Make sure the token doesn't get used a second time.
        $this->database->delete('users_cross_auth')
          ->condition('id', $id)->execute();

        // Bind this session with the one in which the token was generated.
        // This will allow us to logout the user on all sessions on which he
        // was logged in with a token, from only one logout.
        $this->addSessionToFamily($authRow['session_family_id']);

        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Removes expired tokens and session clusters.
   */
  public function cleanExpired() {
    $this->database->delete('users_cross_auth')
      ->condition('created', time() - self::TOKEN_LIFESPAN, '<')
      ->execute();

    // Currently all session clusters are stored until they are logged out or
    // until specified constant time has elapsed.
    // TODO: Figure out a better way to clean up session clusters.
    $this->database->delete('users_session_family')
      ->condition('expires', time(), '<')
      ->execute();
  }

  /**
   * Logs out user from all connected sessions.
   */
  public function logoutSessionFamily() {
    // Acquire connected sessions.
    $sessionFamilyId = $this->getSessionFamilyId();
    $sessions = $this->database->select('users_session_family', 'u')
      ->fields('u', ['sessions'])
      ->condition('id', $sessionFamilyId)
      ->execute()->fetchField();

    if (!empty($sessions)) {
      // By deleting these sessions from the drupal core session table the users
      // will be logged out.
      $this->database->delete('sessions')
        ->condition('sid', explode('/', $sessions), 'in')
        ->execute();

      // Remove the cluster.
      $this->database->delete('users_session_family')
        ->condition('id', $sessionFamilyId)->execute();
    }
  }

  /**
   * Returns the family ID of the current session.
   *
   * @return int
   *    The family ID.
   */
  protected function getSessionFamilyId() {
    if (array_key_exists(self::SESSION_FAMILY_KEY, $_SESSION)) {
      // Make sure that the stored family ID still exists in database.
      $exists = $this->database->select('users_session_family', 'u')
        ->fields('u', ['id'])
        ->condition('id', $_SESSION[self::SESSION_FAMILY_KEY])
        ->execute()->fetchField();

      if ($exists !== FALSE) {
        return $_SESSION[self::SESSION_FAMILY_KEY];
      }
    }

    // Create session family for current session as there isn't one yet.
    $familyId = $this->database->insert('users_session_family')
      ->fields([
        'sessions' => $this->getCurrentSessionHash(),
        'expires' => time() + self::SESSION_FAMILY_LIFETIME,
      ])->execute();

    $_SESSION[self::SESSION_FAMILY_KEY] = $familyId;
    return $familyId;
  }

  /**
   * Adds a session to a specific session family.
   *
   * @param int $id
   *    The session family ID to add to.
   */
  protected function addSessionToFamily($id) {
    // If we already got one then don't do anything.
    if (
      array_key_exists(self::SESSION_FAMILY_KEY, $_SESSION) &&
      $_SESSION[self::SESSION_FAMILY_KEY] == $id
    ) {
      return;
    }

    $this->database->update('users_session_family')
      ->expression('sessions', 'CONCAT_WS(\'/\', sessions, :session)', [
        ':session' => $this->getCurrentSessionHash(),
      ])->execute();

    $_SESSION[self::SESSION_FAMILY_KEY] = $id;
  }

  /**
   * Gets the current hashed session ID.
   *
   * @return string
   *    The hashed session ID.
   */
  protected function getCurrentSessionHash() {
    if (empty($this->sessionHash)) {
      $this->sessionHash = Crypt::hashBase64($this->currentRequest->getSession()
        ->getId());
    }

    return $this->sessionHash;
  }

}

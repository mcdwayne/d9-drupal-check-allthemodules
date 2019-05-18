<?php

namespace Drupal\intercom;

use GuzzleHttp\Exception\RequestException;
use Intercom\IntercomClient;
use Drupal\Core\Site\Settings;
use Psr\Log\LoggerInterface;

/**
 * Intercom Service.
 */
class IntercomApiService {

  private $accessToken;
  private $logger;

  /**
   * IntercomProxyClient constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger to be used by this service.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;

    $this->accessToken = Settings::get('intercom_access_token');
    if (empty($this->accessToken)) {
      $this->logError('No access_token provided.');
      return;
    }
    $this->client = new IntercomClient($this->accessToken, NULL);
  }

  /**
   * Create a user through the Intercom API.
   *
   * @param array $data
   *   The data that will be passed to Intercom.
   */
  public function createUser(array $data) {
    if (!isset($data['email']) || !isset($this->client)) {
      return;
    }

    $this->client->users->create($data);
  }

  /**
   * Retrieve an Intercom user by email.
   *
   * @param string $email
   *   The email to query for.
   *
   * @return array|null
   *   An array with users found or null if no user was found.
   */
  public function getUserByEmail($email) {
    if (!isset($this->client)) {
      return NULL;
    }
    try {
      $user = $this->client->users->getUsers(["email" => $email]);
    }
    catch (RequestException $exception) {
      $user = NULL;
    }

    return $user;
  }

  /**
   * Find an Intercom user by a Drupal user id.
   *
   * @param string $user_id
   *   The Drupal user id to query for.
   *
   * @return array|null
   *   An array of users found or null if no user was found.
   */
  public function getUserByUserId($user_id) {
    if (!isset($this->client)) {
      return NULL;
    }
    try {
      $user = $this->client->users->getUsers(["user_id" => $user_id]);
    }
    catch (RequestException $exception) {
      $user = NULL;
    }

    return $user;
  }

  /**
   * Log an error.
   *
   * @param string $message
   *   The error message to log.
   * @param bool $throw_exception
   *   Whether the $message should be thrown as exception.
   *
   * @throws \Exception
   *   If $throw_exception is true.
   */
  public function logError($message, $throw_exception = FALSE) {
    $message = str_replace($this->accessToken, 'my_super_secret_access_token', $message);
    $this->logger->error($message);

    if ($throw_exception === TRUE) {
      throw new \Exception($message);
    }
  }

}

<?php

namespace Drupal\github_connect;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Connection;
use Drupal\user\Entity\User;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * WeatherService.
 */
class GithubConnectService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Class constructor.
   */
  public function __construct(Connection $database, Client $client) {
    $this->database = $database;
    $this->httpClient = $client;
  }

  /**
   * Save the new GitHub user in github_connect_users.
   */
  public function githubConnectSaveGithubUser($account, $token) {
    $github_user = self::githubConnectGetGithubUserInfo($token);

    $this->database->insert('github_connect_authmap')
      ->fields(array(
        'uid' => $account->id(),
        'provider' => 'github_connect',
        'authname' => $github_user['html_url'],
      ))
      ->execute();

    // Store GitHub user with token.
    if ($account) {
      $this->database->insert('github_connect_users')
        ->fields(array(
          'uid' => $account->id(),
          'access_token' => $token,
          'timestamp' => REQUEST_TIME,
        ))
        ->execute();
    }
  }

  /**
   * Get the user info provided by github.
   *
   * @param string $token
   *   The token for the github user.
   *
   * @return array|mixed
   *   User object.
   */
  public function githubConnectGetGithubUserInfo($token) {
    $cache = &drupal_static(__FUNCTION__);

    if (!is_null($cache)) {
      $github_user = $cache;
    }
    else {
      // Collects the User information from GitHub.
      $client = $this->httpClient;
      $ghuser = $client->request('GET', 'https://api.github.com/user?access_token=' . $token . '&scope=user&token_type=bearer');
      // TODO pass timeout value.
      $data = (string) $ghuser->getBody();
      $github_user = Json::decode($data);
      $github_user_emails = self::githubConnectGetGithubUserEmails($token);
      $github_user['email'] = $github_user_emails[0]['email'];
    }

    return $github_user;
  }

  /**
   * Get the private email addresses from the user.
   */
  public function githubConnectGetGithubUserEmails($token) {
    $cache = &drupal_static(__FUNCTION__);

    if (!is_null($cache)) {
      $github_user_emails = $cache;
    }
    else {
      // Collects the User information from GitHub.
      $client = $this->httpClient;
      $ghuser = $client->request('GET', 'https://api.github.com/user/emails?access_token=' . $token . '&scope=user&token_type=bearer');
      $data = (string) $ghuser->getBody();
      $github_user_emails = Json::decode($data);
    }

    return $github_user_emails;
  }

  /**
   * Register new user.
   */
  public function githubConnectRegister($github_user, $token) {
    $username = $github_user['login'];

    $userinfo = array(
      'name' => $username,
      'mail' => $github_user['email'],
      'pass' => user_password(),
      'status' => 1,
      'access' => REQUEST_TIME,
      'init' => $github_user['email'],
    );

    // Import GitHub profile picture on first login.
    if (empty(user_load_by_mail($github_user['email'])) && !empty($github_user['avatar_url'])) {
      // Download profile image from github.
      $avatar_url = file_get_contents($github_user['avatar_url']);
      $file = file_save_data($avatar_url, 'public://' . time() . '_avatars.png', NULL);
      $userinfo['user_picture'] = $file->id();
    }

    $account = entity_create('user', $userinfo);
    $account->save();

    if ($account) {
      $this->githubConnectSaveGithubUser($account, $token);

      // Log in the stored user.
      self::githubConnectUserLogin($account);
      global $base_url;
      $response = new RedirectResponse($base_url);
      $response->send();
      return;
    }
    else {
      drupal_set_message($this->t('Error saving new user.'), 'error');
      return;
    }
  }

  /**
   * Log the user with the given account in.
   */
  public function githubConnectUserLogin($account) {
    $uid = $account->id();
    $user = User::load($uid);
    user_login_finalize($user);
  }

}

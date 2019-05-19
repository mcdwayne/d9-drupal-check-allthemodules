<?php

namespace Drupal\strava\Manager;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\user\UserInterface;

class UserManager {

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * UserManager constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   * @param LoggerChannelFactoryInterface $logger_factory
   * @param EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Returns a drupal user object
   *
   * @param string $email
   *
   * @return object|bool
   */
  public function getUserByEmail($email) {
    $existing_user = user_load_by_mail($email);

    return $existing_user;
  }

  /**
   * Registrates a new Drupal user object.
   *
   * @param string $email
   * @param string $username
   * @param string $picture
   *
   * @return FALSE|EntityInterface
   *   Returns User object when new users is saved, FALSE if unsuccessful.
   */
  public function registerUser($email, $username, $picture) {
    if (!$email || !$username) {
      $this->loggerFactory
        ->get('strava')
        ->warning('Failed to create a new user with email: @email and username: @username', [
          '@email' => $email,
          '@username' => $username,
        ]);

      return FALSE;
    }

    if ($this->registrationBlocked()) {
      $this->loggerFactory
        ->get('strava')
        ->warning('User registration is disabled for this website. Failed to create a new user with email: @email and username: @username', [
          '@email' => $email,
          '@username' => $username,
        ]);

      return FALSE;
    }

    $user_fields = [
      'name' => $this->generateUniqueUsername($username),
      'mail' => $email,
      'init' => $email,
      'pass' => user_password(48),
      'status' => $this->getNewUserStatus(),
      'user_picture' => $this->getImageForUser($picture),
    ];

    $new_user = $this->entityTypeManager
      ->getStorage('user')
      ->create($user_fields);

    try {
      $new_user->save();

      $this->loggerFactory
        ->get('strava')
        ->notice('New user created. Username @username, UID: @uid', [
          '@username' => $new_user->getAccountName(),
          '@uid' => $new_user->id(),
        ]);

      return $new_user;
    }
    catch (EntityStorageException $e) {
      $this->loggerFactory
        ->get('strava')
        ->error('Could not create new user. Exception: @message', ['@message' => $e->getMessage()]);
    }

    return FALSE;
  }

  /**
   * Logs in a drupal user.
   *
   * @param UserInterface $user
   */
  public function loginUser(UserInterface $user) {
    user_login_finalize($user);
  }

  /**
   * Checks if user registration is blocked in Drupal account settings.
   *
   * @return bool
   *   True if registration is blocked
   *   False if registration is not blocked
   */
  protected function registrationBlocked() {
    // Check if Drupal account registration settings is Administrators only.
    if ($this->configFactory
        ->get('user.settings')
        ->get('register') == 'admin_only') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Ensures that Drupal usernames will be unique.
   *
   * Drupal usernames will be generated so that the user's full name on Strava
   * will become user's Drupal username. This method will check if the username
   * is already used and appends a number until it finds the first available
   * username.
   *
   * @param string $name
   *   User's full name on Strava.
   *
   * @return string
   *   Unique username
   */
  protected function generateUniqueUsername($name) {
    $base = trim($name);
    $i = 1;
    $candidate = $base;
    while ($this->loadUserByProperty('name', $candidate)) {
      $i++;
      $candidate = $base . " " . $i;
    }
    return $candidate;
  }

  /**
   * Returns the status for new users.
   *
   * @return int
   *   Value 0 means that new accounts remain blocked and require approval.
   *   Value 1 means that visitors can register new accounts without approval.
   */
  protected function getNewUserStatus() {
    if ($this->configFactory
        ->get('user.settings')
        ->get('register') == 'visitors') {
      return 1;
    }

    return 0;
  }

  /**
   * Returns the image for a new user.
   *
   * @param string $picture
   *   Url of the image file.
   *
   * @return mixed
   */
  protected function getImageForUser($picture) {

    $file = system_retrieve_file($picture, NULL, TRUE, FILE_EXISTS_REPLACE);

    return $file;
  }

  /**
   * Loads existing Drupal user object by given property and value.
   *
   * Note that first matching user is returned. Email address and account name
   * are unique so there can be only zero or one matching user when
   * loading users by these properties.
   *
   * @param string $field
   *   User entity field to search from.
   * @param string $value
   *   Value to search for.
   *
   * @return \Drupal\user\Entity\User|false
   *   Drupal user account if found
   *   False otherwise
   */
  public function loadUserByProperty($field, $value) {
    $users = $this->entityTypeManager
      ->getStorage('user')
      ->loadByProperties([$field => $value]);

    if (!empty($users)) {
      return current($users);
    }

    // If user was not found, return FALSE.
    return FALSE;
  }
}
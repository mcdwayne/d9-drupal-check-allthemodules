<?php

namespace Drupal\enhanced_user;
use Drupal\Component\Utility\Unicode;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class UserCreator.
 */
class UserCreator implements UserCreatorInterface {

  /**
   * Drupal\language\ConfigurableLanguageManagerInterface definition.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;
  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;
  /**
   * Constructs a new UserCreator object.
   */
  public function __construct(ConfigurableLanguageManagerInterface $language_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->languageManager = $language_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Create a new user account.
   *
   * @param string $name
   *   User's name on Provider.
   * @param string $email
   *   User's email address.
   *
   * @return \Drupal\user\Entity\User|false
   *   Drupal user account if user was created
   *   False otherwise
   * @throws \Exception
   */
  public function createUser($name, $email) {

    // Check if site configuration allows new users to register.
    if ($this->isRegistrationDisabled()) {
      throw new \Exception('Register new users is not allow.');
    }

    // Get the current UI language.
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

    // Initializes the user fields.
    $fields = $this->getUserFields($name, $email, $langcode);

    // Create new user account.
    /** @var \Drupal\user\Entity\User $new_user */
    $new_user = \Drupal::entityTypeManager()->getStorage('user')
      ->create($fields);

    $new_user->save();

    return $new_user;
  }

  /**
   * Checks if user registration is disabled.
   *
   * @return bool
   *   True if registration is disabled
   *   False if registration is not disabled
   */
  protected function isRegistrationDisabled() {
    // Check if Drupal account registration settings is Administrators only
    // OR if it is disabled in Social Auth Settings.
    if (\Drupal::config('user.settings')->get('register') == 'admin_only' || \Drupal::config('social_auth.settings')->get('user_allowed') == 'login') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Returns an array of fields to initialize the creation of the user.
   *
   * @param string $name
   *   User's name on Provider.
   * @param string $email
   *   User's email address.
   * @param string $langcode
   *   The current UI language.
   *
   * @return array
   *   Fields to initialize for the user creation.
   */
  protected function getUserFields($name, $email, $langcode) {
    $fields = [
      'name' => $this->generateUniqueUsername($name),
      'mail' => $email,
      'init' => $email,
      'pass' => $this->userPassword(32),
      'status' => $this->getNewUserStatus(),
      'langcode' => $langcode,
      'preferred_langcode' => $langcode,
      'preferred_admin_langcode' => $langcode,
    ];

    return $fields;
  }

  /**
   * Ensures that Drupal usernames will be unique.
   *
   * Drupal usernames will be generated so that the user's full name on Provider
   * will become user's Drupal username. This method will check if the username
   * is already used and appends a number until it finds the first available
   * username.
   *
   * @param string $name
   *   User's full name on provider.
   *
   * @return string
   *   Unique drupal username.
   */
  protected function generateUniqueUsername($name) {
    $max_length = 60;
    $name = Unicode::substr($name, 0, $max_length);
    $name = str_replace(' ', '', $name);
    $name = strtolower($name);

    // Add a trailing number if needed to make username unique.
    $base = $name;
    $i = 1;
    $candidate = $base;
    while ($this->loadUserByProperty('name', $candidate)) {
      // Calculate max length for $base and truncate if needed.
      $max_length_base = $max_length - strlen((string) $i) - 1;
      $base = Unicode::substr($base, 0, $max_length_base);
      $candidate = $base . $i;
      $i++;
    }

    // Trim leading and trailing whitespace.
    $candidate = trim($candidate);

    return $candidate;
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
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties([$field => $value]);

    if (!empty($users)) {
      return current($users);
    }

    // If user was not found, return FALSE.
    return FALSE;
  }

  /**
   * Wrapper for user_password.
   *
   * We need to wrap the legacy procedural Drupal API functions so that we are
   * not using them directly in our own methods. This way we can unit test our
   * own methods.
   *
   * @param int $length
   *   Length of the password.
   *
   * @return string
   *   The password.
   *
   * @see user_password
   */
  protected function userPassword($length) {
    return user_password($length);
  }

  /**
   * Returns the status for new users.
   *
   * @return int
   *   Value 0 means that new accounts remain blocked and require approval.
   *   Value 1 means that visitors can register new accounts without approval.
   */
  protected function getNewUserStatus() {
    if (\Drupal::config('user.settings')
        ->get('register') == 'visitors') {
      return 1;
    }

    return 0;
  }
}

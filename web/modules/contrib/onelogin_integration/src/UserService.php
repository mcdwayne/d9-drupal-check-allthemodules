<?php

namespace Drupal\onelogin_integration;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class UserService for the OneLogin Integration module.
 *
 * Creates a user with the username / password coming from the OneLogin account
 * the user uses to login with. This service is triggered when someone is trying
 * to log in through OneLogin, but no account exists yet for the given
 * username / email.
 *
 * @package Drupal\onelogin_integration
 */
class UserService implements UserServiceInterface {

  /**
   * The variable that holds an instance of ConfigFactoryInterface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The variable that holds an instance of EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * UserService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Reference to ConfigFactoryInterface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Reference to TypeManagerInterface.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Creates a user.
   *
   * @param string $username
   *   The username for the new user.
   * @param string $email
   *   The email for the new user.
   *
   * @return mixed
   *   Returns a user object.
   */
  public function createUser($username, $email) {
    $user = $this->entityTypeManager->getStorage('user')->create(
      [
        'name'                     => $username,
        'mail'                     => $email,
        'pass'                     => user_password(16),
        'enforceIsNew'             => TRUE,
        'init'                     => $email,
        'defaultLangcode'          => 'en',
        'preferred_langcode'       => 'en',
        'preferred_admin_langcode' => 'en',
        'status'                   => 0,
      ]
    );

    $user->save();
    $this->entityTypeManager->getStorage('user')->load($user->id());

    drupal_set_message("User with uid " . $user->id() . " saved!\n");

    return $user;
  }

}

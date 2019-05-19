<?php

namespace Drupal\steam_login;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * UserAlter class.
 */
class UserAlter implements UserAlterInterface {

  /**
   * User storage.
   *
   * @var \Drupal\user\UserStorage
   */
  protected $userStorage;

  /**
   * User Alter constructor.
   *
   * @param \Drupal\core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alterUserName(string $name, $account) {
    if (substr($name, 0, 6) == 'steam-') {
      if ($user = $this->userStorage->load($account->id())) {
        $steam_username = current($user->get('field_steam_username')->getValue());
        $name = isset($steam_username['value']) ? urldecode($steam_username['value']) : $name;
      }
    }

    return $name;
  }

  /**
   * User route title callback.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   *
   * @return string|array
   *   The user account name as a render array or an empty string if $user is
   *   NULL.
   */
  public function alterUserTitle(UserInterface $user = NULL) {
    return $user ? $user->getDisplayName() : '';
  }

}

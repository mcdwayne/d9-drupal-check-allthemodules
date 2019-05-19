<?php

namespace Drupal\syncart\Service;

use Drupal\user\UserInterface;

/**
 * Interface AuthServiceInterface.
 */
interface AuthServiceInterface {

  /**
   * Поиск пользователя по email.
   *
   * @var string $email
   */
  public function getUserEmail(string $email);

  /**
   * Создаём пользователя.
   *
   * @var array $info
   *
   * @return \Drupal\user\UserInterface
   *   The created cart user.
   */
  public function createUser(array $info);

  /**
   * Создаём профиль.
   *
   * @param \Drupal\user\UserInterface $user
   *   The created cart user.
   * @param array $info
   *   Array checkout form.
   *
   * @return \Drupal\profile\Entity\Profile
   *   The created cart profile.
   */
  public function createProfile(UserInterface $user, array $info);

}

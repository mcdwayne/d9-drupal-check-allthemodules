<?php

namespace Drupal\system_user\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\null_user\NullUser;
use Drupal\user\UserInterface;

/**
 * A class for retrieving system users.
 */
class SystemUserManager {

  /**
   * The name of the base field.
   */
  const FIELD_NAME = 'system_user';

  /**
   * The user storage instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $userStorage;

  /**
   * SystemUserManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The user storage instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * Return all system users.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Return all the system users.
   */
  public function getAll() {
    $user_ids = $this->query()->execute();

    return $this->userStorage->loadMultiple($user_ids);
  }

  /**
   * Return the first system user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The first system user or a NullUser is one was not found.
   */
  public function getFirst() {
    $user_ids = $this->query()
      ->range(0, 1)
      ->execute();

    if (empty($user_ids)) {
      return new NullUser();
    }

    return $this->userStorage->load(reset($user_ids));
  }

  /**
   * Return a count of the total number of system users.
   *
   * @return int
   *   The number of system users.
   */
  public function getCount() {
    return $this->query()->count()->execute();
  }

  /**
   * Generate the base query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   Return query object for user storage.
   */
  private function query() {
    return $this->userStorage
      ->getQuery()
      ->condition(self::FIELD_NAME, 1);
  }

  /**
   * Determine if a user is a system user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to check.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  public static function isSystemUser(UserInterface $user) {
    return $user->get(static::FIELD_NAME)->getString() == 1;
  }

}

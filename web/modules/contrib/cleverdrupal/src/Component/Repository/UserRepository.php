<?php

namespace Drupal\cleverreach\Component\Repository;

use Drupal;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * User (Recipient) Repository.
 */
class UserRepository {

  /**
   * Gets user IDs by provided filters. If filters are not set, all users will be returned.
   *
   * @param array|null $filterBy
   *   Example: ['field' => 'test', 'condition' => '=', 'value' => 'something'].
   * @param string|null $language
   *   Language filter.
   *
   * @return array
   *   Array of User IDs.
   */
  public function getAllIds($filterBy = NULL, $language = NULL) {
    $query = Drupal::entityQuery('user');
    $query->condition('uid', 0, '<>');

    if (is_array($filterBy) && !empty($filterBy)) {
      foreach ($filterBy as $filter) {
        $condition = isset($filter['condition']) ? $filter['condition'] : '=';
        $query->condition($filter['field'], $filter['value'], $condition, $language);
      }
    }

    if (!$ids = $query->execute()) {
      return [];
    }

    return $ids;
  }

  /**
   * Gets all user IDs by role ID.
   *
   * @param int $roleId
   *   Required user role ID.
   *
   * @return array
   *   Array of User IDs.
   */
  public function getIdsByRoleId($roleId) {
    return $this->getAllIds([['field' => 'roles', 'value' => $roleId]]);
  }

  /**
   * Gets users by provided filters. If filters are not set, all users will be returned.
   *
   * @param array|null $filterBy
   *   Example: ['field' => 'test', 'condition' => '=', 'value' => 'something'].
   * @param string|null $language
   *   Language filter.
   *
   * @return \Drupal\user\Entity\User[]
   */
  public function get($filterBy = NULL, $language = NULL) {
    return User::loadMultiple($this->getAllIds($filterBy, $language));
  }

  /**
   * Gets user role names by given user.
   *
   * @param \Drupal\user\Entity\User $user
   *   Required user roles.
   *
   * @return array
   *   List of role names
   */
  public function getRoleNamesByUser(User $user) {
    $result = [];
    $roles = Role::loadMultiple($user->getRoles());

    /** @var \Drupal\user\Entity\Role $role */
    foreach ($roles as $role) {
      $result[] = $role->label();
    }

    return $result;
  }

}

<?php

namespace Drupal\permission_ui;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\RoleInterface;
use Drupal\user\RoleListBuilder as UserRoleListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of user role entities.
 *
 * @see \Drupal\user\RoleListBuilder
 */
class RoleListBuilder extends UserRoleListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Name');
    $header['user'] = t('Users');
    return $header + DraggableListBuilder::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['user'] = [
      '#markup' => $this->t('N/A'),
    ];
    $user_count = $this->getUserCount($entity->id());
    if ($user_count !== NULL) {
      $count_string = $this->formatPlural($user_count, '1 user', '@count users');
      $url = Url::fromRoute('view.user_admin_people.page_1', [], ['query' => ['role' => $entity->id()]]);
      $row['user'] = Link::fromTextAndUrl($count_string, $url)->toRenderable();
    }
    return $row + DraggableListBuilder::buildRow($entity);
  }

  /**
   * Gets user count by role.
   *
   * @param int $role_id
   *   Role object.
   *
   * @return mixed
   *   Count of users have given role id.
   */
  protected function getUserCount($role_id) {
    if ($role_id != RoleInterface::ANONYMOUS_ID && $role_id !== RoleInterface::AUTHENTICATED_ID) {
      return \Drupal::database()->select('user__roles', 'ur')
        ->fields('ur', ['roles_target_id'])
        ->condition('ur.roles_target_id', $role_id)
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    return NULL;
  }

}

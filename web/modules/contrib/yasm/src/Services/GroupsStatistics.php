<?php

namespace Drupal\yasm\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Groups statistics class.
 */
class GroupsStatistics implements GroupsStatisticsInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function countNodes(GroupInterface $group) {
    // Use static query for performance.
    $query = $this->connection
      ->query('SELECT count(id) as count FROM {group_content_field_data} WHERE gid = :id AND type LIKE :type', [
        ':id' => $group->id(),
        ':type' => $group->getGroupType()->id() . '-group_node-%',
      ]);
    $result = $query->fetchAll();

    return $result[0]->count;
  }

  /**
   * {@inheritdoc}
   */
  public function countNodesByType(GroupInterface $group) {
    // Use static query for performance.
    $query = $this->connection
      ->query('SELECT count(id) as count, type FROM {group_content_field_data} WHERE gid = :id AND type LIKE :type GROUP by type', [
        ':id' => $group->id(),
        ':type' => $group->getGroupType()->id() . '-group_node-%',
      ]);
    $results = $query->fetchAll();

    $count_types = [];
    if (!empty($results)) {
      $content_types = $this->entityTypeManager->getStorage('node')->loadMultiple();

      foreach ($results as $result) {
        if (isset($result->type)) {
          $ctype = (string) $result->type;
          $ctype = substr($ctype, strrpos($ctype, '-') + 1);
          $count_types[$ctype] = [
            'type'  => isset($content_types[$ctype]) ? $content_types[$ctype]->label() : $ctype,
            'count' => $result->count,
          ];
        }
      }
    }

    return $count_types;
  }

  /**
   * {@inheritdoc}
   */
  public function countMembers(GroupInterface $group) {
    // Use static query for performance.
    $query = $this->connection
      ->query('SELECT count(DISTINCT entity_id) as count FROM {group_content_field_data} WHERE gid = :id AND type = :type', [
        ':id' => $group->id(),
        ':type' => $group->getGroupType()->id() . '-group_membership',
      ]);
    $result = $query->fetchAll();

    return $result[0]->count;
  }

  /**
   * {@inheritdoc}
   */
  public function countMembersByRole(GroupInterface $group) {
    $query = $this->connection
      ->query('SELECT
          count(DISTINCT gd.entity_id) as count,
          gr.group_roles_target_id as role
        FROM {group_content__group_roles} gr
        INNER JOIN {group_content_field_data} gd
        ON gr.entity_id = gd.id
        WHERE gd.gid = :id
        AND gr.bundle = :type
        GROUP BY gr.group_roles_target_id', [
          ':id' => $group->id(),
          ':type' => $group->getGroupType()->id() . '-group_membership',
        ]);
    $results = $query->fetchAll();

    $count_roles = [];
    if (!empty($results)) {

      $roles = $this->getGroupRoles();
      foreach ($results as $result) {
        $count_roles[$result->role] = [
          'role'  => isset($roles[$result->role]) ? $roles[$result->role] : $result->role,
          'count' => $result->count,
        ];
      }
    }

    return $count_roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupRoles() {
    $query = $this->connection
      ->query('SELECT group_roles_target_id as role FROM {group_content__group_roles}');
    $results = $query->fetchAll();

    $group_roles = [];
    if (!empty($results)) {
      foreach ($results as $result) {
        $role = $this->entityTypeManager->getStorage('group_role')->load($result->role);
        if (!empty($role)) {
          $group_roles[$result->role] = $role->label();
        }
      }
    }

    return $group_roles;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entityTypeManager) {
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

}

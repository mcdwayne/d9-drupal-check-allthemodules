<?php

namespace Drupal\access_by_entity;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Session\AccountInterface;

/**
 * Class AccessByEntityStorage.
 *
 * @package Drupal\access_by_entity
 */
class AccessByEntityStorage implements AccessByEntityStorageInterface {

  /**
   * Connexion database.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * AccessByEntityStorageService constructor.
   *
   * @param \Drupal\Core\Database\Driver\mysql\Connection $database
   *   The connection to the database.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The connection to the database.
   */
  public function __construct(Connection $database, AccountInterface $currentUser) {
    $this->database = $database;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function clear($entity_id, $entity_type_id) {
    return $this->database->delete('access_by_entity')->condition(
      'entity_id', $entity_id
    )->condition(
        'entity_type_id', $entity_type_id
      )->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function findBy($params) {
    $query = $this->database->select('access_by_entity', 'abe')->fields(
      'abe', ['rid', 'perm', 'entity_id']
    );
    foreach ($params as $param) {
      $operator = '=';
      if (is_array($param['value'])) {
        $operator = 'IN';
      }
      $query->condition($param['key'], $param['value'], $operator);
    }
    return $query->execute()->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function save($entity_id, $entity_type_id, $role_name, $data) {
    try {
      foreach ($data as $key => $value) {
        $this->database->insert('access_by_entity')->fields(
          [
            'entity_id' => $entity_id,
            'rid' => $role_name,
            'perm' => $key,
            'entity_type_id' => $entity_type_id,
          ]
        )->execute();
      }
      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Check if the current User can access this item or not.
   * If the current user has a role already saved in "access_by_entity"
   * this mean he has a restriction and cannot access this item with $op.
   *
   * @param string $entityId
   *    Id if the entity.
   * @param string $entity_type_id
   *    Type entity id (node, user ...)
   * @param string $op
   *    Operation (view|edit|delete)
   *
   * @return bool
   */
  public function isAccessAllowed($entityId, $entity_type_id, $op) {
    $user = $this->currentUser;
    $roles = $user->getRoles();
    if (count($roles) > 1) {
      $roles = array_diff($roles, ['authenticated']);
    }
    $data = $this->findBy(
      [
        ['key' => 'entity_id', 'value' => $entityId],
        ['key' => 'perm', 'value' => $op],
        ['key' => 'rid', 'value' => $roles],
        ['key' => 'entity_type_id', 'value' => $entity_type_id],
      ]
    );
    return empty($data);
  }

}

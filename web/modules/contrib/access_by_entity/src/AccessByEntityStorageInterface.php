<?php

namespace Drupal\access_by_entity;

/**
 * Interface AccessByEntityStorageInterface.
 *
 * @package Drupal\access_by_entity
 */
interface AccessByEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function clear($entity_id, $entity_type_id);

  /**
   * {@inheritdoc}
   */
  public function save($entity_id, $entity_type_id, $role_name, $data);

  /**
   * {@inheritdoc}
   */
  public function isAccessAllowed($entityId, $entity_type_id, $op);

}

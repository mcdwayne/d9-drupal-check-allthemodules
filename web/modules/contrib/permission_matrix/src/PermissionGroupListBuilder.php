<?php

namespace Drupal\permission_matrix;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Permission group entities.
 */
class PermissionGroupListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Group Name');
    $header['permissions'] = $this->t('Permissions');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['permissions'] = $this->getGroupPermissions($entity->get('permissions'));
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

  /**
   * Permissions comma seperated.
   */
  private function getGroupPermissions($permissions) {
    $perm = [];
    foreach ($permissions as $key => $val) {
      if ($val <> "") {
        $perm[] = $val;
      }
    }
    return implode(", ", $perm);
  }

}

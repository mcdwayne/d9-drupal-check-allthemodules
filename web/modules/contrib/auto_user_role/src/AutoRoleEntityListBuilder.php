<?php

namespace Drupal\auto_user_role;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Auto role entity entities.
 */
class AutoRoleEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Auto role');
    $header['role'] = $this->t('Role');
    $header['field'] = $this->t('Field');
    $header['field_value'] = $this->t('Field_value');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['role'] = $entity->getRole();
    $row['field'] = $entity->getField();
    $row['field_value'] = $entity->getFieldValue();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}

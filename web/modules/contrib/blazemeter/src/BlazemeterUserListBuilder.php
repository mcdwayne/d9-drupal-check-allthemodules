<?php

/**
 * @file
 * Contains \Drupal\blazemeter\BlazemeterUserListBuilder.
 */

namespace Drupal\blazemeter;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Blazemeter user entities.
 */
class BlazemeterUserListBuilder extends ConfigEntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Username');
    $header['password'] = $this->t('Password');
    $header['id'] = $this->t('ID');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['username'] = $entity->username();
    $row['password'] = $entity->password();
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

}

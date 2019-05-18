<?php

namespace Drupal\abstractpermissions\Form;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

class PermissionAbstractionListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Machine name');
    $header['label'] = $this->t('Deriver');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $data['id'] = $entity->id();
    $data['label'] = $entity->label();
    return $data + parent::buildRow($entity);
  }

  /**
   * @inheritDoc
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if (isset($operations['edit'])) {
      // Remove destination, we handle redirection ourself.
      $operations['edit']['url'] = $entity->toUrl('edit-form');
    }
    return $operations;
  }


}

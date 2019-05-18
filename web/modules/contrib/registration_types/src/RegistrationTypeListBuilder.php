<?php

namespace Drupal\registration_types;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Registration type entities.
 */
class RegistrationTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Registration type');
    $header['id'] = $this->t('Machine name');
    $header['description'] = $this->t('Description');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['description'] = $entity->getDescription();
    $row['status'] = $entity->getEnabled() ? $this->t('enabled') : $this->t('disabled');
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}

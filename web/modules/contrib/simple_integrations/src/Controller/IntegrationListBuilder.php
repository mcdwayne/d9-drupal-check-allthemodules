<?php

namespace Drupal\simple_integrations\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Integration entities.
 */
class IntegrationListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Integration');
    $header['active'] = $this->t('Active');
    $header['debug_mode'] = $this->t('Debug mode');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['active'] = $entity->isActive() ? $this->t('Yes') : $this->t('No');
    $row['debug_mode'] = $entity->isDebugMode() ? $this->t('Yes') : $this->t('No');

    return $row + parent::buildRow($entity);
  }

}

<?php

namespace Drupal\stats;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\stats\Entity\StatProcessorInterface;

/**
 * Provides a listing of Stat processor entities.
 */
class StatProcessorListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Stat processor');
    $header['id'] = $this->t('Machine name');
    $header['trigger'] = $this->t('Trigger');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if (!$entity instanceof  StatProcessorInterface) {
      return;
    }

    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['trigger'] = $entity->getTriggerBundle() ? $entity->getTriggerEntityType() . ':' . $entity->getTriggerBundle() : $entity->getTriggerEntityType();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}

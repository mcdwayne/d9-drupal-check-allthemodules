<?php

namespace Drupal\changelog;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Changelog entities.
 */
class ChangelogEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Changelog');
    $header['id'] = $this->t('Machine name');
    $header['created'] = $this->t('Log date');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\changelog\Entity\ChangelogEntity $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $time = $entity->getCreatedTime();
    $time = \Drupal::service('date.formatter')->format($time, 'short');
    $row['created'] = $time;
    return $row + parent::buildRow($entity);
  }

}

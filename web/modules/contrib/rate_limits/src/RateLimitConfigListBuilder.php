<?php

namespace Drupal\rate_limits;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Rate Limit Config entities.
 */
class RateLimitConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Rate Limit Config');
    $header['id'] = $this->t('Machine name');
    $header['tags'] = $this->t('Route Tags');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['tags'] = [
      'data' => [
        '#theme' => 'item_list',
        '#items' => $entity->get('tags'),
      ],
    ];
    return $row + parent::buildRow($entity);
  }

}

<?php

namespace Drupal\owms;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of OWMS Data entities.
 */
class OwmsDataListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('OWMS Data object');
    $header['endpoint'] = $this->t('Endpoint');
    $header['items'] = $this->t('Items');
    $header['deprecated'] = $this->t('Deprecated items');
    $header['last_updated'] = $this->t('Last updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\owms\Entity\OwmsDataInterface $entity */
    $row['label'] = $entity->label();
    $row['endpoint'] = $entity->getEndpointUrl();
    $row['items'] = count($entity->getItems());
    $row['deprecated'] = count($entity->getDeprecatedItems());
    $last_updated = \Drupal::state()->get('owms.last_checked');
    if ($last_updated) {
      $row['last_updated'] = \Drupal::service('date.formatter')
        ->format($last_updated);
    }
    return $row + parent::buildRow($entity);
  }
}

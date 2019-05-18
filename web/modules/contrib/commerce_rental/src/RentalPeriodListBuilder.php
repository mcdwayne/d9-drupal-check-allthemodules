<?php

namespace Drupal\commerce_rental;

use Drupal\Core\Entity\EntityInterface;
use Drupal\commerce_rental\Entity\RentalPeriodType;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines the list builder for rental periods.
 */
class RentalPeriodListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Title');
    $header['type'] = $this->t('Type');
    $header['time'] = $this->t('Time');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $period_type = RentalPeriodType::load($entity->bundle());

    $row = [];
    $row['title'] = $entity->label();
    $row['type'] = $period_type->label();
    $row['time'] = $entity->get('time_units')->value . ' ' . $entity->get('granularity')->value;

    return $row + parent::buildRow($entity);
  }

}

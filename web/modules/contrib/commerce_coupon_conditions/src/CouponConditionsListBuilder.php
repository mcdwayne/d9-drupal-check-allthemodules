<?php

namespace Drupal\commerce_coupon_conditions;

use Drupal\commerce_promotion\CouponListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the list builder for coupons with conditions.
 */
class CouponConditionsListBuilder extends CouponListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = parent::buildHeader();
    $header['start_date'] = $this->t('Start date');
    $header['end date_date'] = $this->t('End date');

    // Move operations at the end.
    $operations = $header['operations'];
    unset($header['operations']);
    $header['operations'] = $operations;

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_coupon_conditions\Entity\CouponInterface $entity */
    $row = parent::buildRow($entity);
    $row['start_date'] = $entity->getStartDate() ? $entity->getStartDate()->format('M jS Y') : '—';
    $row['end_date'] = $entity->getEndDate() ? $entity->getEndDate()->format('M jS Y') : '—';

    // Move operations at the end.
    $operations = $row['operations'];
    unset($row['operations']);
    $row['operations'] = $operations;

    return $row;
  }
}

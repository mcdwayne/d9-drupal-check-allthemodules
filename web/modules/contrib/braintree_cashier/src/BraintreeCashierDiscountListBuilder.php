<?php

namespace Drupal\braintree_cashier;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Braintree Cashier Discount entities.
 *
 * @ingroup braintree_cashier
 */
class BraintreeCashierDiscountListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Discount ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\braintree_cashier\\Entity\BraintreeCashierDiscount */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.braintree_cashier_discount.edit_form',
      ['braintree_cashier_discount' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}

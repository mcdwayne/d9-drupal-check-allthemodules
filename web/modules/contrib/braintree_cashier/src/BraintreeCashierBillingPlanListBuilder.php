<?php

namespace Drupal\braintree_cashier;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of billing plan entities.
 *
 * @ingroup braintree_cashier
 */
class BraintreeCashierBillingPlanListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Billing plan ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlan */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.braintree_cashier_billing_plan.edit_form',
      ['braintree_cashier_billing_plan' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}

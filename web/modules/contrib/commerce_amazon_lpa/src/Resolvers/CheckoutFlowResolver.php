<?php

namespace Drupal\commerce_amazon_lpa\Resolvers;

use Drupal\commerce\ConditionManagerInterface;
use Drupal\commerce_checkout\Resolver\CheckoutFlowResolverInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Checkout flow resolver for amazon.
 */
class CheckoutFlowResolver implements CheckoutFlowResolverInterface {

  /**
   * The condition manager.
   *
   * @var \Drupal\commerce\ConditionManagerInterface
   */
  protected $conditionManager;

  /**
   * The checkout flow storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $checkoutFlowStorage;

  /**
   * Constructor.
   */
  public function __construct(ConditionManagerInterface $condition_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->conditionManager = $condition_manager;
    $this->checkoutFlowStorage = $entity_type_manager->getStorage('commerce_checkout_flow');
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(OrderInterface $order) {
    /** @var \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface $condition */
    $condition = $this->conditionManager->createInstance('amazon_order');
    if ($condition->evaluate($order)) {
      return $this->checkoutFlowStorage->load('amazon_pay');
    }
  }

}

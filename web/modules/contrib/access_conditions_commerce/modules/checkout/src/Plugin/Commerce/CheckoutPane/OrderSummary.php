<?php

namespace Drupal\access_conditions_commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\access_conditions_commerce\AccessConditionsCommerceCheckoutPaneTrait;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\OrderSummary as OrderSummaryBase;

/**
 * Provides the Order summary pane with access conditions visibility.
 *
 * @CommerceCheckoutPane(
 *   id = "access_conditions_order_summary",
 *   label = @Translation("Order summary with access conditions"),
 *   default_step = "_disabled",
 *   wrapper_element = "container",
 * )
 */
class OrderSummary extends OrderSummaryBase {

  use AccessConditionsCommerceCheckoutPaneTrait;

}

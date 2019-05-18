<?php

namespace Drupal\access_conditions_commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\access_conditions_commerce\AccessConditionsCommerceCheckoutPaneTrait;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\BillingInformation as BillingInformationBase;

/**
 * Provides the billing information pane with access conditions visibility.
 *
 * @CommerceCheckoutPane(
 *   id = "access_conditions_billing_information",
 *   label = @Translation("Billing information with access conditions"),
 *   default_step = "_disabled",
 *   wrapper_element = "fieldset",
 * )
 */
class BillingInformation extends BillingInformationBase {

  use AccessConditionsCommerceCheckoutPaneTrait;

}

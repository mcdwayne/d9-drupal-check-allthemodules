<?php

namespace Drupal\access_conditions_commerce_payment\Plugin\Commerce\CheckoutPane;

use Drupal\access_conditions_commerce\AccessConditionsCommerceCheckoutPaneTrait;
use Drupal\commerce_payment\Plugin\Commerce\CheckoutPane\PaymentInformation as PaymentInformationBase;

/**
 * Provides the payment information pane with access conditions visibility.
 *
 * @CommerceCheckoutPane(
 *   id = "access_conditions_payment_information",
 *   label = @Translation("Payment information with access conditions"),
 *   default_step = "_disabled",
 *   wrapper_element = "fieldset",
 * )
 */
class PaymentInformation extends PaymentInformationBase {

  use AccessConditionsCommerceCheckoutPaneTrait;

}

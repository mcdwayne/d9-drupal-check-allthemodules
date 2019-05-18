<?php

namespace Drupal\access_conditions_commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\access_conditions_commerce\AccessConditionsCommerceCheckoutPaneTrait;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\Login as LoginBase;

/**
 * Provides the login pane with access conditions visibility.
 *
 * @CommerceCheckoutPane(
 *   id = "access_conditions_login",
 *   label = @Translation("Login or continue as guest with access conditions"),
 *   default_step = "_disabled",
 * )
 */
class Login extends LoginBase {

  use AccessConditionsCommerceCheckoutPaneTrait;

}

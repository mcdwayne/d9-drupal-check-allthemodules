<?php

namespace Drupal\access_conditions_commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\access_conditions_commerce\AccessConditionsCommerceCheckoutPaneTrait;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\Review as ReviewBase;

/**
 * Provides the review pane with access conditions visibility.
 *
 * @CommerceCheckoutPane(
 *   id = "access_conditions_review",
 *   label = @Translation("Review with access conditions"),
 *   default_step = "_disabled",
 * )
 */
class Review extends ReviewBase {

  use AccessConditionsCommerceCheckoutPaneTrait;

}

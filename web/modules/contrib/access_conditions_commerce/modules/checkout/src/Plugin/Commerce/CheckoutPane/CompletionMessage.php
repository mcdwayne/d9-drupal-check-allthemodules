<?php

namespace Drupal\access_conditions_commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\access_conditions_commerce\AccessConditionsCommerceCheckoutPaneTrait;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CompletionMessage as CompletionMessageBase;

/**
 * Provides the completion message pane with access conditions visibility.
 *
 * @CommerceCheckoutPane(
 *   id = "access_conditions_completion_message",
 *   label = @Translation("Completion message with access conditions"),
 *   default_step = "_disabled",
 * )
 */
class CompletionMessage extends CompletionMessageBase {

  use AccessConditionsCommerceCheckoutPaneTrait;

}

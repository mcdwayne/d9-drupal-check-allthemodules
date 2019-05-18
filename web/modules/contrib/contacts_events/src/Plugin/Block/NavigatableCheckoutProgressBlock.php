<?php

namespace Drupal\contacts_events\Plugin\Block;

use Drupal\commerce_checkout\Plugin\Block\CheckoutProgressBlock;
use Drupal\contacts_events\Plugin\Commerce\CheckoutFlow\BookingFlow;
use Drupal\Core\Url;

/**
 * Provides a checkout progress block.
 *
 * @Block(
 *   id = "navigatable_checkout_progress",
 *   admin_label = @Translation("Checkout progress - Navigatable"),
 *   category = @Translation("Commerce")
 * )
 */
class NavigatableCheckoutProgressBlock extends CheckoutProgressBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Have to duplicate the whole of CheckoutProgressBlock::build
    // as the base method doesn't provide a way to customize just the output
    // Our custom version just adds the link into the steps collection.
    $order = $this->routeMatch->getParameter('commerce_order');
    if (!$order) {
      // The block is being rendered outside of the checkout page.
      return [];
    }
    $checkout_flow = $this->checkoutOrderManager->getCheckoutFlow($order);
    $checkout_flow_plugin = $checkout_flow->getPlugin();
    $configuration = $checkout_flow_plugin->getConfiguration();
    if (empty($configuration['display_checkout_progress'])) {
      return [];
    }

    // Prepare the steps as expected by the template.
    $steps = [];
    $visible_steps = $checkout_flow_plugin->getVisibleSteps();
    $requested_step_id = $this->routeMatch->getParameter('step');
    $current_step_id = $this->checkoutOrderManager->getCheckoutStepId($order, $requested_step_id);
    $current_step_index = array_search($current_step_id, array_keys($visible_steps));
    $index = 0;
    foreach ($visible_steps as $step_id => $step_definition) {
      if ($index < $current_step_index) {
        $position = 'previous';
      }
      elseif ($index == $current_step_index) {
        $position = 'current';
      }
      else {
        $position = 'next';
      }
      $index++;
      // Hide hidden steps until they are reached.
      if (!empty($step_definition['hidden']) && $position != 'current') {
        continue;
      }

      $steps[] = [
        'id' => $step_id,
        'label' => $step_definition['display_label'] ?? $step_definition['label'],
        'position' => $position,
        'link' => Url::fromRoute(BookingFlow::ROUTE_NAME, [
          'commerce_order' => $order->id(),
          'step' => $step_id,
        ])->toString(),
      ];
    }

    return [
      '#attached' => [
        'library' => ['commerce_checkout/checkout_progress'],
      ],
      '#theme' => 'contacts_events_checkout_progress',
      '#steps' => $steps,
    ];
  }

}

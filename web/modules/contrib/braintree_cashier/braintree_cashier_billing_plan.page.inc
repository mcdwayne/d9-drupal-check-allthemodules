<?php

/**
 * @file
 * Contains billing_plan.page.inc.
 *
 * Page callback for Braintree Cashier Billing plan entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Prepares variables for Braintree Cashier Billing plan templates.
 *
 * Default template: billing_plan.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_braintree_cashier_billing_plan(array &$variables) {
  /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface $billing_plan */
  $billing_plan = $variables['elements']['#braintree_cashier_billing_plan'];
  $view_mode = $variables['elements']['#view_mode'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  if ($view_mode == 'overview' && !empty($variables['content']['call_to_action'])) {
    // Output the call to action as a link with a query parameter indicating the
    // billing plan selected when the view mode is overview.
    $variables['content']['call_to_action'] = [
      '#type' => 'link',
      '#title' => $billing_plan->getCallToAction(),
      '#url' => Url::fromRoute('braintree_cashier.signup_form', [], [
        'query' => [
          'plan_id' => $billing_plan->id(),
        ],
      ]),
      '#attributes' => ['class' => ['call-to-action']],
      '#weight' => $variables['content']['call_to_action']['#weight'],
    ];
  }
}

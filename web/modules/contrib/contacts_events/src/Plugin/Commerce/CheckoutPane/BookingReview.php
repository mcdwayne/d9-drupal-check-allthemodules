<?php

namespace Drupal\contacts_events\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\contacts_events\Plugin\Commerce\CheckoutFlow\BookingFlow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the review pane.
 *
 * @CommerceCheckoutPane(
 *   id = "booking_review",
 *   label = @Translation("Booking Review"),
 *   default_step = "review",
 * )
 */
class BookingReview extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    // Get the effective step weight, excluding the summary.
    $steps = $this->checkoutFlow->getSteps();
    unset($steps['summary']);
    $steps = array_keys($steps);
    $step_weights = array_flip($steps);

    /** @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface[] $enabled_panes */
    $enabled_panes = array_filter($this->checkoutFlow->getPanes(), function ($pane) {
      return !in_array($pane->getStepId(), ['_sidebar', '_disabled']);
    });

    $route_name = $this->checkoutFlow instanceof BookingFlow ? $this->checkoutFlow::ROUTE_NAME : 'commerce_checkout.form';

    $pane_form['_intro']['#weight'] = -9;
    $pane_form['_intro']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Welcome to your Booking Dashboard'),
    ];
    $pane_form['_intro']['subtitle'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('You can come back and manage your booking progress from here at every step of the process.'),
    ];

    $continue_step = $this->order->get('checkout_step')->value ?: reset($steps);
    $pane_form['_intro']['continue'] = [
      '#type' => 'link',
      '#title' => $this->t('Continue my booking'),
      '#url' => Url::fromRoute($route_name, [
        'commerce_order' => $this->order->id(),
        'step' => $continue_step,
      ]),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
      '#access' => $continue_step != 'review',
    ];

    foreach ($enabled_panes as $pane_id => $pane) {
      if ($summary = $pane->buildPaneSummary()) {
        // BC layer for panes which still return rendered strings.
        if ($summary && !is_array($summary)) {
          $summary = [
            '#markup' => $summary,
          ];
        }

        $label = isset($summary['#title']) ? $summary['#title'] : $pane->getDisplayLabel();

        $pane_form[$pane_id] = [
          '#type' => 'fieldset',
          '#title' => $label,
          '#weight' => ($step_weights[$pane->getStepId()] * 1000) + $pane->getConfiguration()['weight'],
        ];

        $pane_form[$pane_id]['summary'] = $summary;

        $definition = $pane->getPluginDefinition();
        if ($pane->getPluginId() == 'booking_payment_information') {
          if (!($this->checkoutFlow instanceof BookingFlow) || $this->checkoutFlow->needsPayment()) {
            $definition['review_link'] = $this->t('Review and pay now');
          }
          elseif ($this->checkoutFlow->needsConfirmation()) {
            $definition['review_link'] = $this->t('Review and confirm now');
          }
        }

        if ($pane->isVisible() && !empty($definition['review_link'])) {
          $pane_form[$pane_id]['link'] = [
            '#type' => 'link',
            '#title' => $definition['review_link'],
            '#url' => Url::fromRoute($route_name, [
              'commerce_order' => $this->order->id(),
              'step' => $pane->getStepId(),
            ]),
            '#attributes' => [
              'class' => ['button', 'button--secondary'],
            ],
          ];
        }
      }
    }

    // Bump the payment info to the top.
    if (isset($pane_form['booking_payment_information'])) {
      $pane_form['booking_payment_information']['#weight'] = -1;
    }

    return $pane_form;
  }

}

<?php

namespace Drupal\contacts_events\Plugin\Commerce\CheckoutFlow;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides the default multistep checkout flow.
 *
 * @CommerceCheckoutFlow(
 *   id = "booking_flow",
 *   label = "Booking Flow",
 * )
 */
class BookingFlow extends CheckoutFlowWithPanesBase {

  const ROUTE_NAME = 'booking_flow';

  /**
   * {@inheritdoc}
   */
  public function getSteps() {
    return [
      'summary' => [
        'label' => $this->t('Dashboard'),
        'has_sidebar' => FALSE,
      ],
      'tickets' => [
        'label' => $this->t('Tickets'),
        'previous_label' => $this->t('Back to tickets'),
        'next_label' => $this->t('Add tickets'),
        'has_sidebar' => FALSE,
      ],
      'address' => [
        'label' => $this->t('Delivery & Billing Address'),
        'previous_label' => $this->t('Back to details'),
        'next_label' => $this->t('Enter your details'),
        'has_sidebar' => FALSE,
      ],
      'terms' => [
        'label' => $this->t('Terms and Conditions'),
        'next_label' => $this->t('Review and pay'),
        'has_sidebar' => FALSE,
        'hidden' => TRUE,
      ],
      'review' => [
        'display_label' => $this->t('Payment'),
        'label' => $this->t('Payment (Review)'),
        'next_label' => $this->t('Review and pay'),
        'has_sidebar' => FALSE,
      ],
      'payment' => [
        'display_label' => $this->t('Payment'),
        'label' => $this->t('Payment (Process)'),
        'next_label' => $this->t('Make payment'),
        'has_sidebar' => FALSE,
        'hidden' => TRUE,
      ],
      'complete' => [
        'has_sidebar' => FALSE,
        'hidden' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $step_id = NULL) {
    // If complete, redirect to the dashboard with a message.
    if ($step_id == 'complete') {
      $this->messenger()->addStatus($this->t('Thank you for confirming your booking.'));
      $payment_order_manager = \Drupal::service('commerce_payment.order_manager');
      $payment_order_manager->updateTotalPaid($this->getOrder());
      $this->redirectToStep('summary');
    }
    return parent::buildForm($form, $form_state, $step_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getPanes() {
    // Do not load disabled panes.
    // By forcing them to never be loaded, we bypass the issue where the
    // Payment_Process pane requires the order to have at least 1 item.
    if (empty($this->panes)) {
      $panes = array_filter(parent::getPanes(), function (CheckoutPaneInterface $p) {
        return $p->getStepId() != '_disabled';
      });
      $this->panes = $panes;
    }

    return $this->panes;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStepId($step_id) {
    // Redirect to the summary if in doubt.
    return parent::getNextStepId($step_id) ?? 'summary';
  }

  /**
   * {@inheritdoc}
   */
  public function redirectToStep($step_id) {
    // Only update the order if we are progressing to a later state.
    $this->order->set('checkout_step', $step_id);
    $this->onStepChange($step_id);
    $this->order->save();

    // Use our route rather than the default commerce_checkout.form.
    throw new NeedsRedirectException(Url::fromRoute(self::ROUTE_NAME, [
      'commerce_order' => $this->order->id(),
      'step' => $step_id,
    ])->toString());
  }

  /**
   * {@inheritdoc}
   */
  protected function onStepChange($step_id) {
    // Prevent changing step to a previous step or storing the complete step.
    $current_step = $this->entityTypeManager
      ->getStorage('commerce_order')
      ->loadUnchanged($this->order->id())
      ->get('checkout_step')->value;

    // Get the order of the steps.
    $steps = array_keys($this->getSteps());

    $current_pos = array_search($current_step, $steps);
    $new_pos = array_search($step_id, $steps);
    $payment_pos = array_search('payment', $steps);
    if ($current_step == 'complete' || $new_pos < $current_pos) {
      // If the old position was earlier than the payment step, preserve it.
      // Otherwise set it to review.
      $this->order->set('checkout_step', $current_pos < $payment_pos ? $current_step : 'review');
    }

    // Lock the order while on the 'payment' checkout step. Unlock elsewhere.
    if ($step_id == 'payment') {
      $this->order->lock();
    }
    elseif ($step_id != 'payment') {
      $this->order->unlock();
    }
    // Place the order.
    if ($step_id == 'complete') {
      $transition = $this->order->getState()->getWorkflow()->getTransition('place');
      $this->order->getState()->applyTransition($transition);
    }
  }

  /**
   * Whether there is anything to pay on the booking.
   *
   * @return bool
   *   Whether there is anything to pay.
   */
  public function needsPayment() {
    $balance = $this->order->getBalance();
    return $balance && !$balance->isZero();
  }

  /**
   * Whether there is anything on the booking that needs confirming.
   *
   * @return bool
   *   Whether there is anything to confirm.
   */
  public function needsConfirmation() {
    $transitions = $this->order->getState()->getTransitions();
    return isset($transitions['place']);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // The summary has no actions.
    if ($form['#step_id'] == 'summary') {
      return [];
    }

    $steps = $this->getVisibleSteps();
    $next_step_id = $this->getNextStepId($form['#step_id']);
    $previous_step_id = $this->getPreviousStepId($form['#step_id']);
    $has_next_step = $next_step_id && isset($steps[$next_step_id]['next_label']);
    $has_previous_step = $previous_step_id && isset($steps[$previous_step_id]['previous_label']);

    $actions = [
      '#type' => 'actions',
      '#access' => $has_next_step,
    ];

    if ($has_next_step) {
      if ($next_step_id == 'payment' && !$this->needsPayment() && !$this->needsConfirmation()) {
        $parameters = [
          'commerce_order' => $this->order->id(),
          'step' => 'summary',
        ];
        $options = [
          'attributes' => ['class' => ['btn', 'btn-primary']],
        ];
        $actions['next'] = Link::createFromRoute($this->t('Go to summary'), self::ROUTE_NAME, $parameters, $options)->toRenderable();
      }
      else {
        $label = $steps[$next_step_id]['next_label'];
        if ($next_step_id == 'payment' && !$this->needsPayment()) {
          $label = $this->t('Confirm my booking');
        }
        $actions['next'] = [
          '#type' => 'submit',
          '#value' => $label,
          '#button_type' => 'primary',
          '#submit' => ['::submitForm'],
        ];
      }
    }

    if ($has_previous_step) {
      $label = $steps[$previous_step_id]['previous_label'];
      $parameters = [
        'commerce_order' => $this->order->id(),
        'step' => $previous_step_id,
      ];
      $options = [
        'attributes' => ['class' => ['btn', 'btn-light']],
      ];
      $actions['previous'] = Link::createFromRoute($label, self::ROUTE_NAME, $parameters, $options)->toRenderable();
      // Ensure some spacing.
      $actions['previous']['#prefix'] = ' ';
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $redirect = $form_state->getRedirect();
    if (!isset($redirect) || ($redirect instanceof Url && $redirect->getRouteName() == 'commerce_checkout.form')) {
      $form_state->setRedirect(self::ROUTE_NAME, [
        'commerce_order' => $this->order->id(),
        'step' => $this->getNextStepId($form['#step_id']),
      ]);
    }
  }

}

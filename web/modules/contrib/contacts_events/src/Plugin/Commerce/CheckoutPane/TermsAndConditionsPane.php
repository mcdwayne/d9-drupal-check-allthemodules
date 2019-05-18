<?php

namespace Drupal\contacts_events\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Terms and Conditions pane.
 *
 * @CommerceCheckoutPane(
 *   id = "terms_and_conditions",
 *   label = @Translation("Terms and Conditions"),
 *   default_step = "terms_and_conditions",
 *   wrapper_element = "container",
 * )
 */
class TermsAndConditionsPane extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['terms'] = $this->order->event->entity->get('terms_and_conditions')->view();

    $terms = $this->order->get('terms_and_conditions_confirmed');
    $pane_form['agreed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I agree to the terms and conditions'),
      '#default_value' => $terms->value,
      '#required' => TRUE,
      '#disabled' => $terms->value,
    ];

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    // Mark the Ts & Cs as confirmed.
    $this->order->set('terms_and_conditions_confirmed', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    // Not visible if we have already agreed.
    if ($this->order->hasField('terms_and_conditions_confirmed') && $this->order->get('terms_and_conditions_confirmed')->value) {
      return FALSE;
    }

    // Not visible if we don't have an event.
    if (!$this->order->hasField('event')) {
      return FALSE;
    }
    $event = $this->order->event->entity;
    if (!$event) {
      return FALSE;
    }

    // Not visible if the event has no Ts & Cs.
    if (!$event->hasField('terms_and_conditions')) {
      return FALSE;
    }
    return !$event->get('terms_and_conditions')->isEmpty();
  }

}

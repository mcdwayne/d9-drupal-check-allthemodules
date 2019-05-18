<?php

namespace Drupal\commerce_pagseguro_transp\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\commerce_payment\Entity\PaymentMethod;
// Unused statement use Drupal\Core\Utility\LinkGenerator; // Unused statement.
use Drupal\Core\Url;

/**
 * Provides the completion message pane.
 *
 * @CommerceCheckoutPane(
 *   id = "completion_message",
 *   label = @Translation("Completion message"),
 *   default_step = "complete",
 * )
 */
class CompletionMessage extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['#theme'] = 'commerce_checkout_completion_message';
    $pane_form['#order_entity'] = $this->order;

    // Loading payment method to get the pagseguro link ticket.
    $payment_method_id = $this->order->get('payment_method')->target_id;
    $payment_method = PaymentMethod::load($payment_method_id);
    $payment_method_type = $payment_method->getType()->getPluginId();

    // Only print the link if a Payment Method Type is pagseguro_ticket.
    switch ($payment_method_type) {
      case 'pagseguro_debit':
      case 'pagseguro_ticket':
        $ticket_link = Url::fromUri($payment_method->get('payment_link')->value);
        $link = Link::fromTextAndUrl(t('Click here to proceed with payment'), $ticket_link);
        $link = $link->toRenderable();
        $link['#attributes'] = [
          'id' => 'pagseguro-ticket-link',
          'target' => '_blank',
        ];

        $pane_form['#payment_instructions'] = $link;
        break;
    }

    return $pane_form;
  }

}

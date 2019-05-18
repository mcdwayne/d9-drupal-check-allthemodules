<?php

namespace Drupal\commerce_claim_gift_aid\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\OrderItemType;

/**
 * Provides the gift aid information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "claim_gift_aid",
 *   label = @Translation("Gift Aid Declaration"),
 *   default_step = "review",
 *   wrapper_element = "fieldset",
 * )
 */
class ClaimGiftAid extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $config = \Drupal::config('commerce_claim_gift_aid.commerce_gift_aid_text');
    $pane_form['gift_aid_declaration'] = [
      '#type' => 'checkbox',
      '#title' => $config->get('gift_aid_text'),
      '#default_value' => $this->order->get('gift_aid')->value,
    ];

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValues();
    $gift_aid = !empty($values['claim_gift_aid']['gift_aid_declaration']) ? 1 : 0;
    // Set the claim_gift_aid value on the order as true or false.
    $this->order->set('gift_aid', $gift_aid);
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    // Only show the gift aid pane if the order item type is eligible for
    // gift aid.
    $any_gift_aid_items = FALSE;
    $order_items = $this->order->getItems();
    foreach ($order_items as $item) {
      $config_entity = OrderItemType::load($item->bundle());
      if ($config_entity instanceof OrderItemType && $config_entity->getThirdPartySetting('commerce_claim_gift_aid', 'gift_aid') == TRUE) {
        $any_gift_aid_items = TRUE;
        break;
      }
    }
    return $any_gift_aid_items;
  }

}

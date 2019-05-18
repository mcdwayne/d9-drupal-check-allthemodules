<?php

namespace Drupal\commerce_extra_items\Plugin\Commerce\PromotionOffer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_promotion\Entity\PromotionInterface;

/**
 * Provides an availability to offer extra items with discount for order items.
 *
 * E.g. BOGO (Buy one, get one).
 *
 * @CommercePromotionOffer(
 *   id = "order_item_extra_items_percentage_off",
 *   label = @Translation("Extra items with percentage off discount"),
 *   entity_type = "commerce_order_item",
 * )
 */
class OrderItemExtraItemsPercentageOff extends ExtraItemsOfferBase implements OrderItemExtraItemPercentageOffInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['percentage' => '0'] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getPercentage() {
    return (string) $this->configuration['percentage'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form += parent::buildConfigurationForm($form, $form_state);

    $form['percentage'] = [
      '#type' => 'commerce_number',
      '#title' => $this->t('Percentage discount for the offered extra item'),
      '#default_value' => $this->configuration['percentage'] * 100,
      '#maxlength' => 255,
      '#min' => 0,
      '#max' => 100,
      '#size' => 4,
      '#field_suffix' => $this->t('%'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    if (empty($values['percentage'])) {
      $form_state->setError($form, $this->t('Percentage must be a positive number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['percentage'] = (string) ($values['percentage'] / 100);
  }

  /**
   * {@inheritdoc}
   */
  public function apply(EntityInterface $entity, PromotionInterface $promotion) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $entity */

    // Ignore not existing order items.
    // Drupal\commerce_order\PurchasableEntityPriceCalculator::calculate()
    // creates order item but doesn't save it.
    // It makes problems for the field reference "field_parent_order_item".
    // See Drupal\commerce_extra_items\Plugin\Commerce\PromotionOffer::
    // prepareExtraItem().
    if ($entity->isNew()) {
      return;
    }

    $purchasable_entity = $this->getExtraItemPurchasableEntity();
    $purchasable_entity = $purchasable_entity ?: $entity->getPurchasedEntity();

    if ($purchasable_entity) {
      $order_item = $this->prepareExtraItem($entity, $purchasable_entity);
      $adjustment_amount = $purchasable_entity->getPrice()->multiply($this->getPercentage());
      $adjustment_amount = $this->rounder->round($adjustment_amount);

      $order_item->addAdjustment(new Adjustment([
        'type' => 'promotion',
        // @TODO Change to label from UI when added in #2770731.
        'label' => t('Discount'),
        'amount' => $adjustment_amount->multiply('-1'),
        'percentage' => $this->getPercentage(),
        'source_id' => $promotion->id(),
      ]));

      /*
       * We can't use \Drupal\commerce_cart\CartManagerInterface::addOrderItem()
       * because it resets the checkout step. So It makes impossible to get
       * the next checkout step.
       * Use case: cart->checkout (billing information)->review.
       * You can't get the review step in the example above.
       */
      $order = $entity->getOrder();
      $order_item->save();
      $order->addItem($order_item);

      // Manually trigger CartEvents::CART_ENTITY_ADD.
      if ($purchased_entity = $order_item->getPurchasedEntity()) {
        $quantity = $order_item->getQuantity();
        $saved_order_item = $order_item;
        $event = new CartEntityAddEvent($order, $purchased_entity, $quantity, $saved_order_item);
        $this->eventDispatcher->dispatch(CartEvents::CART_ENTITY_ADD, $event);
      }

    }
  }

}

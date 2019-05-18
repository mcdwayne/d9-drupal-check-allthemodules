<?php

namespace Drupal\commerce_vl\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\PriceSplitterInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_promotion\Entity\CouponInterface;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\OrderPromotionOfferBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\commerce_vl\ViralLoopsIntegratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Viral Loops coupons integration.
 *
 * @CommercePromotionOffer(
 *   id = "viral_loops_offer",
 *   label = @Translation("Viral Loops offer"),
 *   entity_type = "commerce_order",
 * )
 */
class ViralLoopsOffer extends OrderPromotionOfferBase {

  /**
   * The ViralLoopsIntegrator service definition.
   *
   * @var \Drupal\commerce_vl\ViralLoopsIntegratorInterface
   */
  protected $viralLoopsIntegrator;

  /**
   * Applied Viral Loops coupon.
   *
   * @var \Drupal\commerce_promotion\Entity\Coupon
   */
  protected $orderViralLoopsCoupon;

  /**
   * Constructs a new OrderPromotionOfferBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The pluginId for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The rounder.
   * @param \Drupal\commerce_order\PriceSplitterInterface $splitter
   *   The splitter.
   * @param \Drupal\commerce_vl\ViralLoopsIntegratorInterface $viral_loops_integrator
   *   The service for Viral Loops integration.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RounderInterface $rounder,
    PriceSplitterInterface $splitter,
    ViralLoopsIntegratorInterface $viral_loops_integrator
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $rounder, $splitter);
    $this->viralLoopsIntegrator = $viral_loops_integrator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_price.rounder'),
      $container->get('commerce_order.price_splitter'),
      $container->get('commerce_vl.integrator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(EntityInterface $entity, PromotionInterface $promotion) {
    $this->assertEntity($entity);

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    $subtotal_price = $order->getSubTotalPrice();

    $vl_promotion_id = $this->viralLoopsIntegrator->getViralLoopsPromotion(TRUE);

    $vl_coupon = $this->orderViralLoopsCoupon ?? $this->viralLoopsIntegrator->getOrderViralLoopsCoupon($order);
    if ($vl_coupon instanceof CouponInterface) {
      $this->orderViralLoopsCoupon = $vl_coupon;
      switch ($vl_coupon->vl_type->value) {
        case 'amount':
          // @todo - currency code needs to be gotten from VL coupon?
          $amount = new Price($vl_coupon->vl_value->value, $subtotal_price->getCurrencyCode());

          // The promotion amount can't be larger than the subtotal, to avoid
          // potentially having a negative order total.
          if ($amount->greaterThan($subtotal_price)) {
            $amount = $subtotal_price;
          }
          // Split the amount between order items.
          $amounts = $this->splitter->split($order, $amount);
          break;

        case 'percentage':
          $percentage = (string) ($vl_coupon->vl_value->value / 100);
          // Calculate the order-level discount and split it between
          // order items.
          $amount = $subtotal_price->multiply($percentage);
          $amount = $this->rounder->round($amount);
          $amounts = $this->splitter->split($order, $amount, $percentage);
          break;
      }
    }

    foreach ($order->getItems() as $order_item) {
      $has_vl_discount = FALSE;
      foreach ($order_item->getAdjustments() as $order_item_adjustment) {
        if ($order_item_adjustment->getType() == 'promotion'
          && $order_item_adjustment->getSourceId() == $vl_promotion_id
        ) {
          // If there is Viral Loops adjustment then skip adding another one.
          $has_vl_discount = TRUE;
          break;
        }
      }

      if (!$has_vl_discount && isset($amounts[$order_item->id()])) {
        $adjustment_options = [
          'type' => 'promotion',
          'label' => t('Discount'),
          'amount' => $amounts[$order_item->id()]->multiply('-1'),
          'source_id' => $promotion->id(),
        ];
        if ($vl_coupon->vl_type->value == 'percentage') {
          $adjustment_options['percentage'] = $percentage;
        }
        $order_item->addAdjustment(new Adjustment($adjustment_options));
      }
    }
  }

}

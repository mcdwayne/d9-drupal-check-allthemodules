<?php

namespace Drupal\commerce_extra_items\Plugin\Commerce\PromotionOffer;

/**
 * Defines the interface for ExtraItemsPromotionOfferBase.
 */
interface ExtraItemsPromotionOfferBaseInterface {

  /**
   * Gets the quantity of the extra item.
   *
   * @return string
   *   The quantity of the extra item
   */
  public function getQuantity();

  /**
   * Gets the purchasable entity for the extra item.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface|null
   *   The purchasable entity or NULL if none entity is configured.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getExtraItemPurchasableEntity();

}

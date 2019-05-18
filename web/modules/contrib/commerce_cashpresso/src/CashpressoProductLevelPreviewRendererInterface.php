<?php

namespace Drupal\commerce_cashpresso;

use Drupal\commerce\PurchasableEntityInterface;

/**
 * Defines the cashpresso product level preview renderer interface.
 */
interface CashpressoProductLevelPreviewRendererInterface {

  /**
   * Renders the product level preview for the given purchasable entity.
   *
   * The function takes care of existing limitations by configuration settings,
   * like minimum value.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   * @param string[] $adjustment_types
   *   The adjustment types that should be respected upon price calculation.
   * @param int $minimum_price_amount
   *   The minimum price for showing cashpresso product preview. Leave it to
   *   zero for no minimum limit. Defaults to 0.
   * @param bool $enable_direct_checkout
   *   Whether to enable direct checkout. Defaults to TRUE.
   *
   * @return array
   *   The render array. Can be empty, in case of the purchasable entity does
   *   not meet criteria to show the info.
   */
  public function buildCashpressoPreview(PurchasableEntityInterface $purchasable_entity, array $adjustment_types = [], $minimum_price_amount = 0, $enable_direct_checkout = TRUE);

  /**
   * Renders the product level preview as string.
   *
   * This implementation is for convenience, when a rendered string is needed,
   * instead of the render array built by ::buildCashpressoPreview().
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   * @param string[] $adjustment_types
   *   The adjustment types that should be respected upon price calculation.
   * @param int $minimum_price_amount
   *   The minimum price for showing cashpresso product preview. Leave it to
   *   zero for no minimum limit. Defaults to 0.
   * @param bool $enable_direct_checkout
   *   Whether to enable direct checkout. Defaults to TRUE.
   *
   * @return string
   *   A string containing the product level preview for the given purchasable
   *   entity. Can be empty, in case of the purchasable entity does not meet
   *   criteria to show the info.
   */
  public function renderCashpressoPreview(PurchasableEntityInterface $purchasable_entity, array $adjustment_types = [], $minimum_price_amount = 0, $enable_direct_checkout = TRUE);

  /**
   * Renders the static label product level preview for the given entity.
   *
   * The function takes care of existing limitations by configuration settings,
   * like minimum value.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   * @param string[] $adjustment_types
   *   The adjustment types that should be respected upon price calculation.
   * @param int $minimum_price_amount
   *   The minimum price for showing cashpresso product preview. Leave it to
   *   zero for no minimum limit. Defaults to 0.
   * @param bool $enable_direct_checkout
   *   Whether to enable direct checkout. Defaults to TRUE.
   *
   * @return array
   *   The render array. Can be empty, in case of the purchasable entity does
   *   not meet criteria to show the info.
   */
  public function buildCashpressoStaticLabelPreview(PurchasableEntityInterface $purchasable_entity, array $adjustment_types = [], $minimum_price_amount = 0, $enable_direct_checkout = TRUE);

  /**
   * Renders the static label product level preview as string.
   *
   * This implementation is for convenience, when a rendered string is needed,
   * instead of the render array built by ::buildCashpressoPreview().
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   * @param string[] $adjustment_types
   *   The adjustment types that should be respected upon price calculation.
   * @param int $minimum_price_amount
   *   The minimum price for showing cashpresso product preview. Leave it to
   *   zero for no minimum limit. Defaults to 0.
   * @param bool $enable_direct_checkout
   *   Whether to enable direct checkout. Defaults to TRUE.
   *
   * @return string
   *   A string containing the product level preview for the given purchasable
   *   entity. Can be empty, in case of the purchasable entity does not meet
   *   criteria to show the info.
   */
  public function renderCashpressoStaticLabelPreview(PurchasableEntityInterface $purchasable_entity, array $adjustment_types = [], $minimum_price_amount = 0, $enable_direct_checkout = TRUE);

}

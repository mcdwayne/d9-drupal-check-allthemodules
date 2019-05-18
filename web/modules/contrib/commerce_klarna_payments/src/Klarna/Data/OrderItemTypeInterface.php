<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data;

/**
 * An interface to list allowed order item types.
 */
interface OrderItemTypeInterface {

  /**
   * The physical product type.
   *
   * @var string
   */
  public const TYPE_PHYSICAL = 'physical';

  /**
   * The shipping fee product type.
   *
   * @var string
   */
  public const TYPE_SHIPPING_FEE = 'shipping_fee';

  /**
   * The digital product type.
   *
   * @var string
   */
  public const TYPE_DIGITAL = 'digital';

  /**
   * The giftcard product type.
   *
   * @var string
   */
  public const TYPE_GIFT_CARD = 'gift_card';

  /**
   * The store credit product type.
   *
   * @var string
   */
  public const TYPE_STORE_CREDIT = 'store_credit';

  /**
   * The surcharge product type.
   *
   * @var string
   */
  public const TYPE_SURCHARGE = 'surcharge';

}

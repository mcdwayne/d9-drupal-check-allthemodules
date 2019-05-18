<?php

namespace Drupal\commerce_adyen\Adyen;

/**
 * Shopper interaction types.
 *
 * @link https://docs.adyen.com/developers/api-manual#paymentrequests
 */
abstract class ShopperInteraction {

  /**
   * Point-of-sale transactions.
   *
   * The shopper is physically present to make a payment using a secure
   * payment terminal.
   */
  const POS = 'POS';
  /**
   * Mail-order and telephone-order.
   *
   * The shopper is in contact with the merchant via email or telephone.
   */
  const MOTO = 'Moto';
  /**
   * Card on file and/or subscription transactions.
   *
   * The card holder is known to the merchant (returning customer). If
   * the shopper is present (online), you can supply also the CSC to
   * improve authorisation (one-click payment).
   */
  const CONTAUTH = 'ContAuth';
  /**
   * Online transactions.
   *
   * The card holder is present (online). For better authorisation rates we
   * recommend sending the card security code (CSC) along with the request.
   */
  const ECOMMERCE = 'Ecommerce';

}

<?php

namespace Drupal\commerce_cart_refresh;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_cart_refresh\CommerceCartRefreshManagerInterface;
use Drupal\commerce_price\Repository\CurrencyRepository;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * An interface defining a product manager.
 */
class CommerceCartRefreshManager implements CommerceCartRefreshManagerInterface {

  /**
   * {@inheritDoc}
   */
  function __construct(CurrencyRepository $currency_repository, CurrencyFormatterInterface $currency_formatter) {
    $this->currencyRepository = $currency_repository;
    $this->currencyFormatter  = $currency_formatter;
  }

  /**
   * {@inheritDoc}
   */
  public function getPriceDomSelector(ProductVariationInterface $variation) {
    $field_name = 'price';
    return 'product--variation-field--variation_' . $field_name . '__' . $variation->getProductId();
  }

  /**
   * {@inheritDoc}
   */
  public function getCalculatedPrice(int $quantity, ProductVariationInterface $variation, array $options = []) {
    $price         = $variation->get('price')->first()->toPrice();
    $number        = $price->getNumber();
    $currency_code = $price->getCurrencyCode();
    return $this->currencyFormatter->format($number * $quantity, $currency_code, $options);
  }

}

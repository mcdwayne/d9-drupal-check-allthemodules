<?php

namespace Drupal\syncart\Hook;

/**
 * Hook preprocess page class.
 */
class PreprocessCommerceProduct {

  /**
   * Hook.
   */
  public static function hook(&$variables) {
    $variables['product']['cart'] = [
      '#theme' => 'syncart-cart-field',
      '#data' => self::getProductVariation($variables['product_entity']),
    ];
    $variables['product']['favorite'] = [
      '#theme' => 'syncart-favorite-field',
      '#data' => [
        'uid' => \Drupal::currentUser()->id(),
        'product' => $variables['product_entity'],
      ],
    ];
  }

  /**
   * Get product variation price.
   */
  public static function getProductVariation($product) {
    $result = FALSE;
    $currency_storage = \Drupal::entityTypeManager()->getStorage('commerce_currency');
    $currency_formatter = \Drupal::service('commerce_price.currency_formatter');
    $prices = [];
    foreach ($product->variations as $variation) {
      $price = $variation->entity->getPrice()->getNumber();
      $old_price = $variation->entity->field_oldprice->value;
      $currency_code = $variation->entity->getPrice()->getCurrencyCode();
      $currency = $currency_storage->load($currency_code);
      $prices[] = [
        'variation_id' => $variation->entity->id(),
        'price' => $price,
        'old_price' => $old_price,
        'currency_code' => $currency_code,
        'price_format' => !empty($price) ? $currency_formatter->format($price, $currency_code, []) : '',
        'oldprice_format' => !empty($old_price) ? $currency_formatter->format($old_price, $currency_code, []) : '',
        'currency' => $currency->getSymbol(),
      ];
    }
    $result = [
      'product' => $product,
      'prices' => $prices,
      'uid' => \Drupal::currentUser()->id(),
    ];
    return $result;
  }

}

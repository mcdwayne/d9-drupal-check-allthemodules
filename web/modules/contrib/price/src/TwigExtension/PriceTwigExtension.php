<?php

namespace Drupal\price\TwigExtension;

use Drupal\price\Price;

/**
 * Provides Price-specific Twig extensions.
 */
class PriceTwigExtension extends \Twig_Extension {

  /**
   * @inheritdoc
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('price_format', [$this, 'formatPrice']),
    ];
  }

  /**
   * @inheritdoc
   */
  public function getName() {
    return 'price.twig_extension';
  }

  /**
   * Formats a price object/array.
   *
   * Examples:
   * {{ order.getTotalPrice|price_format }}
   * {{ order.getTotalPrice|price_format|default('N/A') }}
   *
   * @param mixed $price
   *   Either a Price object, or an array with number and currency_code keys.
   *
   * @return mixed
   *   A formatted price, suitable for rendering in a twig template.
   *
   * @throws \InvalidArgumentException
   */
  public static function formatPrice($price) {
    if (empty($price)) {
      return '';
    }

    if ($price instanceof Price) {
      $price = $price->toArray();
    }
    if (is_array($price) && isset($price['currency_code']) && isset($price['number'])) {
      $currency_formatter = \Drupal::service('price.currency_formatter');
      return $currency_formatter->format($price['number'], $price['currency_code']);
    }
    else {
      throw new \InvalidArgumentException('The "price_format" filter must be given a price object or an array with "number" and "currency_code" keys.');
    }
  }

}

<?php

namespace Drupal\price_difference_formatter\Plugin\Field\FieldFormatter;

use Drupal\commerce\Context;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\commerce_order\Plugin\Field\FieldFormatter\PriceCalculatedFormatter;

/**
 * Plugin implementation of the 'price_difference_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "price_difference_formatter",
 *   label = @Translation("Price Difference Formatter"),
 *   field_types = {
 *     "commerce_price"
 *   }
 * )
 */
class PriceDifferenceFormatter extends PriceCalculatedFormatter implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    if (!$items->isEmpty()) {
      $context = new Context($this->currentUser, $this->currentStore->getStore(), NULL, [
        'field_name' => $items->getName(),
      ]);
      /** @var \Drupal\commerce\PurchasableEntityInterface $purchasable_entity */
      $purchasable_entity = $items->getEntity();
      $adjustment_types = array_filter($this->getSetting('adjustment_types'));
      $result = $this->priceCalculator->calculate($purchasable_entity, 1, $context, $adjustment_types);
      $calculated_price = $result->getCalculatedPrice();
      $number = $calculated_price->getNumber();
      $currency_code = $calculated_price->getCurrencyCode();
      $options = $this->getFormattingOptions();

      // This is our calculated price with the promotion.
      $price_display = $this->currencyFormatter->format($number, $currency_code, $options);

      // Get price without the promotion.
      $adjustment_types_without_promotion = $adjustment_types;
      unset($adjustment_types_without_promotion['promotion']);
      $result_without_promotion = $this->priceCalculator->calculate($purchasable_entity, 1, $context, $adjustment_types_without_promotion);
      $calculated_price_without_promotion = $result_without_promotion->getCalculatedPrice();
      $number_without_promotion = $calculated_price_without_promotion->getNumber();

      // This is our calculated price without the promotion.
      $price_display_without_promotion = $this->currencyFormatter->format($number_without_promotion, $currency_code, $options);

      if ($price_display_without_promotion != $price_display) {
        $percentage = ($number_without_promotion - $number) / $number_without_promotion;
        $discount = '-' . round($percentage * 100, 2) . '%';
        // Set the base price (old-price), the % and the calculated price.
        $price_display = '<div class="old-price">' . $price_display_without_promotion . '</div>';
        $price_display .= '<div class="percentage-off">' . $discount . '</div>';
        $price_display .= '<div class="new-price">' . $price_display . '</div>';
      }
      else {
        $price_display = '<div class="new-price">' . $price_display . '</div>';
      }

      $elements[0] = [
        '#markup' => $price_display,
        '#cache' => [
          'tags' => $purchasable_entity->getCacheTags(),
          'contexts' => Cache::mergeContexts($purchasable_entity->getCacheContexts(), [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
            'country',
          ]),
        ],
      ];
    }

    return $elements;
  }

}

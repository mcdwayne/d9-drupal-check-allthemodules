<?php

namespace Drupal\commerce_cart_advanced\Plugin\Block;

use Drupal\commerce_cart\Plugin\Block\CartBlock;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;

/**
 * Provides a cart block.
 *
 * @Block(
 *   id = "commerce_cart_advanced_current",
 *   admin_label = @Translation("Current Cart"),
 *   category = @Translation("Commerce")
 * )
 */
class CurrentCartBlock extends CartBlock {

  /**
   * Builds the cart block.
   *
   * @return array
   *   A render array.
   */
  public function build() {
    $cachable_metadata = new CacheableMetadata();
    $cachable_metadata->addCacheContexts(['user', 'session']);

    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $carts */
    $carts = $this->cartProvider->getCurrentCarts();
    $carts = array_filter($carts, function ($cart) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
      // There is a chance the cart may have converted from a draft order, but
      // is still in session. Such as just completing check out. So we verify
      // that the cart is still a cart.
      return $cart->hasItems() && $cart->cart->value;
    });

    $count = 0;
    $cart_views = [];
    if (!empty($carts)) {
      $cart_views = $this->getCartViews($carts);
      foreach ($carts as $cart_id => $cart) {
        foreach ($cart->getItems() as $order_item) {
          $count += (int) $order_item->getQuantity();
        }
        $cachable_metadata->addCacheableDependency($cart);
      }
    }

    $links = [];
    $links[] = [
      '#type' => 'link',
      '#title' => $this->t('Cart'),
      '#url' => Url::fromRoute('commerce_cart.page'),
    ];

    return [
      '#attached' => [
        'library' => ['commerce_cart/cart_block'],
      ],
      '#theme' => 'commerce_cart_block',
      '#icon' => [
        '#theme' => 'image',
        '#uri' => drupal_get_path('module', 'commerce') . '/icons/ffffff/cart.png',
        '#alt' => $this->t('Shopping cart'),
      ],
      '#count' => $count,
      '#count_text' => $this->formatPlural($count, '@count item', '@count items'),
      '#url' => Url::fromRoute('commerce_cart.page')->toString(),
      '#content' => $cart_views,
      '#links' => $links,
      '#cache' => [
        'contexts' => ['cart'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    $cart_cache_tags = [];

    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $carts */
    $carts = $this->cartProvider->getCurrentCarts();
    foreach ($carts as $cart) {
      // Add tags for all carts regardless items or cart flag.
      $cart_cache_tags = Cache::mergeTags(
        $cart_cache_tags,
        $cart->getCacheTags()
      );
    }
    return Cache::mergeTags($cache_tags, $cart_cache_tags);
  }

}

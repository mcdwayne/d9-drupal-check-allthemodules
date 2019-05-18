<?php

namespace Drupal\shopify\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;

/**
 * Provides the shopping cart block.
 *
 * @Block(
 *  id = "shopify_cart",
 *  admin_label = @Translation("Cart")
 * )
 */
class ShopifyCartBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // We can permanently cache this block because the content totals are
    // updated via AJAX through Shopify.
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build[] = [
      '#theme' => 'shopify_cart',
      '#domain' => shopify_shop_info('domain'),
      '#url' => Url::fromUri('https://' . shopify_shop_info('domain') . '/cart'),
      '#attached' => [
        'library' => ['shopify/shopify.js'],
        'drupalSettings' => ['shopify' => shopify_drupal_js_data()],
      ],
    ];
    return $build;
  }

}

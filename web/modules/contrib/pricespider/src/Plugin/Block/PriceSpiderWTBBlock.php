<?php

namespace Drupal\pricespider\Plugin\Block;


use Drupal\Core\Block\BlockBase;

/**
 * Displays 'Where to Buy' Price spider functionality.
 *
 * @Block(
 *   id = "pricespider_wtb_block",
 *   admin_label = @Translation("Where to Buy"),
 * )
 */
class PriceSpiderWTBBlock extends BlockBase {

  public function build() {
    $block = [
      '#theme' => 'pricespider_wtb_block',
      '#attached' => [
        'library' => ['pricespider/pricespider.js'],
        // Add metatags.
        'html_head' => \Drupal::service('pricespider')->getMetaTags(['ps-account', 'ps-config', 'ps-language', 'ps-country']),
      ]
    ];

    return $block;
  }
}
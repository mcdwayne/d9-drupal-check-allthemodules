<?php

namespace Drupal\wallet\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Wallet controller.
 */
class Wallet extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    $urls = array();
    $urls['currency'] = Url::fromRoute('wallet.wallet_currency_settings', array());
    $urls['category'] = Url::fromRoute('wallet.wallet_category_settings', array());
    $urls['transaction'] = Url::fromRoute('wallet.wallet_transaction_settings', array());
    foreach ($urls as $key => $value) {
      $block = array();
      $description = 'List of content of  ' . ucwords(str_replace('_', ' ', $key)) . ' Entity';
      $title = ucwords(str_replace('_', ' ', $key));
      $block['title'] = '';
      $block['description'] = '';
      $block['content'] = array(
        '#theme' => 'admin_block_content',
        '#content' => array(
          $key => array(
            'title' => $title,
            'description' => $description,
            'url' => $value,
          ),
        ),
      );
      if (!empty($block['content']['#content'])) {
        $blocks[$key] = $block;
      }

    }

    if ($blocks) {
      ksort($blocks);
      $build = ['#theme' => 'admin_page', '#blocks' => $blocks];
      return $build;
    }

  }

}

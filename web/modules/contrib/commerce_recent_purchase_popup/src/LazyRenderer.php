<?php

namespace Drupal\commerce_recent_purchase_popup;

/**
 * Class LazyRenderer.
 */
class LazyRenderer {

  /**
   * Renderer for dummy_node_list theme hook.
   */
  public function renderPopup($user_info, $delay, $interval, $time_to_show) {
    return array(
      '#theme' => 'commerce_recent_purchase_popup_block',
      '#user_info' => $user_info,
      '#attached' => [
        'drupalSettings' => [
          'recentPurchasePopupBlockSettings' => [
            'delay' => $delay,
            'interval' => $interval,
            'time_to_show' => $time_to_show,
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['random_recent_pruchase_popup', 'url.site'],
        'keys' => ['commerce_recent_purchase'],
        'max-age' => 60 * 15,
      ],
    );
  }

}

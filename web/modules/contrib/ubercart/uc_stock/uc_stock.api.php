<?php

/**
 * @file
 * Hooks provided by the Stock module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows modules to take action when a stock level is changed.
 *
 * @param string $sku
 *   The SKU whose stock level is being changed.
 * @param int $stock
 *   The stock level before the adjustment.
 * @param int $qty
 *   The amount by which the stock level was changed.
 */
function hook_uc_stock_adjusted($sku, $stock, $qty) {
  $params = [
    'sku' => $sku,
    'stock' => $stock,
    'qty' => $qty,
  ];
  $to = "stock-manager@example.com";

  \Drupal::service('plugin.manager.mail')->mail('uc_stock', 'stock-adjusted', $to, uc_store_mail_recipient_langcode($to), $params, uc_store_email_from());
}

/**
 * @} End of "addtogroup hooks".
 */

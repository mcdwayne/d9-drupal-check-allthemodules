<?php

/**
 * @file
 * API hook documentation for Zuora.
 */

function hook_zuora_hosted_payment_page_build_alter(&$build) {
  // Force overlay style.
  $build['#attached']['drupalSettings']['zuoraPaymentPage']['params']['style'] = 'overlay';

  // If submit enabled is false, change provided button's text.
  if ($build['zuora_payment_page_submit']['#access']) {
    $build['zuora_payment_page_submit']['#title'] = t('Add credit card');
  }
}

<?php

/**
 * @file
 * Provide views data for the Commerce Affirm module.
 */

/**
 * Implements hook_views_data().
 */
function commerce_affirm_views_data() {
  $data = [];
  $data['views']['commerce_affirm_monthly_payment_messaging'] = [
    'title' => t('Affirm  monthly payment  messaging'),
    'help' => t('Displays a price field as an Affirm monthly payment messaging widget. Requires the view to have an order ID or a product variation ID argument.'),
    'area' => [
      'id' => 'commerce_affirm_monthly_payment_messaging',
    ],
  ];
  return $data;
}

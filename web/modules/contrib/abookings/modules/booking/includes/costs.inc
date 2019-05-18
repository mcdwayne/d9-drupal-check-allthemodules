<?php
/**
 * @file
 * Description...
**/

/**
 * CONTENTS
 *
 * get_booking_costs()
 * get_booking_total_cost()
 * build_costs_markup()
**/

use Drupal\node\Entity\Node;


/**
 * Calculates each type of cost and line item, and provides markup for all.
 */
function get_booking_costs(Node $booking) {
  $base_cost = $booking->get('field_base_cost')->getValue();
  // kint($base_cost, '$base_cost');
  if ((! $base_cost) || ($base_cost[0] === [])) {
    $base_cost = [['value' => 0]];
  }
  $base_cost = $base_cost[0]['value'];
  $base_cost = floatval($base_cost);
  // kint($base_cost, '$base_cost');

  $totals = [
    'base_cost' => $base_cost,
    'total_cost' => get_booking_total_cost($booking->id(), $base_cost),
    'total_payable' => NULL,
    'amount_due' => NULL,
  ];

  $balance = $base_cost;

  // Promos
  $promos = get_line_items($booking->id(), 'promotion');
  foreach ($promos as $nid => $promo) {
    $amount = $promo->get('field_amount')->getValue()[0]['value'];
    $balance -= $amount;
  }
  $totals['total_payable'] = $balance;

  // Payments
  $payments = get_line_items($booking->id(), 'payment');
  foreach ($payments as $nid => $payment) {
    $amount = $payment->get('field_amount')->getValue()[0]['value'];
    $balance -= $amount;
  }
  $totals['amount_due'] = $balance;

  return $totals;
}

/**
 * @param $booking_nid
 * @param float $base_cost
 */
function get_booking_total_cost($booking_nid, $base_cost) {
  $balance = $base_cost;

  // Additions
  $additions = get_line_items($booking_nid, 'addition');
  foreach ($additions as $nid => $addition) {
    $amount = $addition->get('field_amount')->getValue()[0]['value'];
    $balance += $amount;
  }

  // Deductions
  $deductions = get_line_items($booking_nid, 'deduction');
  foreach ($deductions as $nid => $deduction) {
    $amount = $deduction->get('field_amount')->getValue()[0]['value'];
    $balance -= $amount;
  }

  // Addons
  $addons = views_get_view_result('data_addons', NULL, $booking_nid);
  foreach ($addons as $index => $resultRow) {
    $addon = $resultRow->_entity;
    $amount = $addon->get('field_amount')->getValue()[0]['value'];
    $balance += $amount;
  }
  return $balance;
}


/**
 * Calculates each type of cost and line item, and provides markup for all.
 */
function build_costs_markup(Node $booking) {

  $booking_costs = get_booking_costs($booking);
  $balance = $booking_costs['base_cost'];

  $markup = '<h3 class="entity-meta__title">Costs</h3>'
    . '<table>';

  // Base cost
  $markup .=
    '  <tr><td>Base cost</td><td>' . $booking_costs['base_cost'] . '</td></tr>';

  // Additions
  $additions = get_line_items($booking->id(), 'addition');
  foreach ($additions as $nid => $addition) {
    $amount = $addition->get('field_amount')->getValue()[0]['value'];
    $markup .=
      '  <tr><td>+ <strong class="tag">Addition</strong> ' . $addition->getTitle() . '</td>'
      . '<td>' . $amount . '</td></tr>';
  }
  if (count($additions) == 0) {
    $markup .= '  <tr><td><em>No additions</em></td><td></td></tr>';
  }

  // Deductions
  $deductions = get_line_items($booking->id(), 'deduction');
  foreach ($deductions as $nid => $deduction) {
    $amount = $deduction->get('field_amount')->getValue()[0]['value'];
    $markup .=
      '  <tr><td>- <strong class="tag">Deduction</strong> ' . $deduction->getTitle() . '</td>'
      . '<td>(' . $amount . ')</td></tr>';
  }
  if (count($deductions) == 0) {
    $markup .= '  <tr><td><em>No deductions</em></td><td></td></tr>';
  }

  // Addons
  $addons = views_get_view_result('data_addons', NULL, $booking->id());
  foreach ($addons as $index => $resultRow) {
    $addon = $resultRow->_entity;
    $amount = $addon->get('field_amount')->getValue()[0]['value'];
    $markup .=
      '  <tr><td>+ <strong class="tag">Addon</strong> ' . $addon->getTitle() . '</td>'
      . '<td>' . $amount . '</td></tr>';
  }
  if (count($addons) == 0) {
    $markup .= '  <tr><td><em>No addons</em></td><td></td></tr>';
  }

  // Total cost
  $markup .=
    '  <tr class="total"><td>= Total cost</td><td>' . $booking_costs['total_cost'] . '</td></tr>';

  // Promos
  $promos = get_line_items($booking->id(), 'promotion');
  foreach ($promos as $nid => $promo) {
    $amount = $promo->get('field_amount')->getValue()[0]['value'];
    $markup .=
      '  <tr><td>- <strong class="tag">Promotion</strong> ' . $promo->getTitle() . '</td>'
      . '<td>(' . $amount . ')</td></tr>';
  }
  if (count($promos) == 0) {
    $markup .= '  <tr><td><em>No promotions</em></td><td></td></tr>';
  }

  // Total payable
  $markup .=
    '  <tr class="total"><td>= Total payable</td><td>' . $booking_costs['total_payable'] . '</td></tr>';

  // Payments
  $payments = get_line_items($booking->id(), 'payment');
  foreach ($payments as $nid => $payment) {
    $amount = $payment->get('field_amount')->getValue()[0]['value'];
    $markup .=
      '  <tr><td>- <strong class="tag">Payment</strong> ' . $payment->getTitle() . '</td>'
      . '<td>(' . $amount . ')</td></tr>';
  }
  if (count($payments) == 0) {
    $markup .= '  <tr><td><em>No payments</em></td><td></td></tr>';
  }

  // Balance due
  $markup .=
    '  <tr class="total"><td>= Balance due</td><td>' . $booking_costs['amount_due'] . '</td></tr>'
    . '</table>';

  return $markup;
}

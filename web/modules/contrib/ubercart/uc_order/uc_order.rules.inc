<?php

/**
 * @file
 * Rules integration for order-related entity events, conditions, and actions.
 */

use Drupal\node\Entity\Node;
use Drupal\uc_order\OrderInterface;

/**
 * Implements hook_rules_data_info().
 */
function uc_order_rules_data_info() {
  $types['uc_order'] = [
    'label' => t('Ubercart order object'),
    'wrap' => TRUE,
    'group' => t('Ubercart'),
  ];
  $types['uc_order_product'] = [
    'label' => t('Ubercart ordered product'),
    'wrap' => TRUE,
    'parent' => 'node',
    'group' => t('Ubercart'),
  ];

  $types['uc_line_item'] = [
    'label' => t('Order line item'),
    'wrap' => TRUE,
    'group' => t('Ubercart'),
    'token type' => FALSE,
  ];

  return $types;
}

/**
 * Implements hook_rules_action_info().
 */
function uc_order_rules_action_info() {
  $order_arg = [
    'type' => 'uc_order',
    'label' => t('Order'),
  ];

  $actions['uc_order_update_status'] = [
    'label' => t('Update the order status'),
    'group' => t('Order'),
    'base' => 'uc_order_action_update_status',
    'parameter' => [
      'order' => $order_arg,
      'order_status' => [
        'type' => 'text',
        'label' => t('Status'),
        'options list' => 'Drupal\uc_order\Entity\OrderStatus::getOptionsList',
      ],
    ],
  ];
  $actions['uc_order_action_add_comment'] = [
    'label' => t('Add a comment to the order'),
    'group' => t('Order'),
    'base' => 'uc_order_action_add_comment',
    'parameter' => [
      'order' => $order_arg,
      'comment' => [
        'type' => 'text',
        'label' => t('Comment'),
      ],
      'comment_type' => [
        'type' => 'text',
        'label' => t('Comment type'),
        'restriction' => 'input',
        'options list' => 'uc_order_action_order_comment_types',
      ],
    ],
  ];
  $actions['uc_order_email'] = [
    'label' => t('Send an order email'),
    'group' => t('Order'),
    'base' => 'uc_order_action_email',
    'parameter' => [
      'order' => $order_arg,
      'from' => [
        'type' => 'text',
        'label' => t('Sender'),
        'description' => t("Enter the 'From' email address, or leave blank to use your store email address. You may use order tokens for dynamic email addresses."),
        'optional' => TRUE,
      ],
      'addresses' => [
        'type' => 'text',
        'label' => t('Recipients'),
        'description' => t('Enter the email addresses to receive the notifications, one on each line. You may use order tokens for dynamic email addresses.'),
      ],
      'subject' => [
        'type' => 'text',
        'label' => t('Subject'),
        'translatable' => TRUE,
      ],
      'message' => [
        'type' => 'text',
        'label' => t('Message'),
        'translatable' => TRUE,
      ],
      'format' => [
        'type' => 'text',
        'label' => t('Message format'),
        'options list' => 'uc_order_message_formats',
      ],
    ],
  ];
  $actions['uc_order_email_invoice'] = [
    'label' => t('Email an order invoice'),
    'group' => t('Order'),
    'base' => 'uc_order_action_email_invoice',
    'parameter' => [
      'order' => $order_arg,
      'from' => [
        'type' => 'text',
        'label' => t('Sender'),
        'description' => t("Enter the 'From' email address, or leave blank to use your store email address. You may use order tokens for dynamic email addresses."),
        'optional' => TRUE,
      ],
      'addresses' => [
        'type' => 'text',
        'label' => t('Recipients'),
        'description' => t('Enter the email addresses to receive the invoice, one on each line. You may use order tokens for dynamic email addresses.'),
      ],
      'subject' => [
        'type' => 'text',
        'label' => t('Subject'),
        'translatable' => TRUE,
      ],
      'template' => [
        'type' => 'text',
        'label' => t('Invoice template'),
        'options list' => 'uc_order_template_options',
        'restriction' => 'input',
      ],
      'view' => [
        'type' => 'text',
        'label' => t('Included information'),
        'options list' => 'uc_order_action_email_invoice_view_options',
        'restriction' => 'input',
      ],
    ],
  ];

  return $actions;
}

/**
 * Checks the current order state.
 */
function uc_order_condition_order_state($order, $order_state) {
  return $order->getStateId() == $order_state;
}

/**
 * Options callback.
 *
 * @see uc_order_condition_order_state()
 */
function uc_order_condition_order_state_options() {
  return uc_order_state_options_list();
}

/**
 * Checks that the order has one of the selected delivery countries.
 */
function uc_order_condition_delivery_country($order, $countries) {
  return in_array($order->delivery_country, $countries);
}

/**
 * Checks that the order has one of the selected billing countries.
 */
function uc_order_condition_billing_country($order, $countries) {
  return in_array($order->billing_country, $countries);
}

/**
 * Checks that the order has the selected combination of product classes.
 *
 * @param \Drupal\uc_order\OrderInterface $order
 *   The order to check.
 * @param array $product_classes
 *   An array of strings containing the product classes (node content
 *   types) to check against.
 * @param bool $required
 *   TRUE to require all product classes be present in the order. FALSE
 *   to require at least one be present.
 * @param bool $forbidden
 *   TRUE to require that only the listed product classes be present. FALSE
 *   to allow products with other classes.
 *
 * @return bool
 *   Whether the order meets the specified conditions.
 */
function uc_order_condition_has_product_class(OrderInterface $order, array $product_classes, $required, $forbidden) {
  $order_product_classes = [];
  foreach ($order->products as $product) {
    if (!empty($product->type)) {
      // If present, use the product type from {uc_order_products}.data.type.
      $order_product_classes[] = $product->type;
    }
    else {
      // Otherwise, use the node type. If the node can't be loaded, ignore
      // this product.
      $node = Node::load($product->nid);
      if (!empty($node)) {
        $order_product_classes[] = $node->type;
      }
    }
  }
  $required_product_classes = array_intersect($product_classes, $order_product_classes);
  if ($required) {
    $required_check = ($required_product_classes == $product_classes);
  }
  else {
    $required_check = (bool) count($required_product_classes);
  }
  if ($forbidden) {
    $forbidden_product_classes = array_diff($order_product_classes, $product_classes);
    $forbidden_check = (bool) count($forbidden_product_classes);
  }
  else {
    $forbidden_check = FALSE;
  }

  return $required_check && !$forbidden_check;
}

/**
 * Options callback.
 *
 * @return array
 *   Associative array of all Ubercart product classes indexed by class ID.
 *
 * @see uc_order_condition_has_product_class()
 */
function uc_order_condition_has_product_class_classes_options() {
  $options = [];

  $result = db_query('SELECT * FROM {uc_product_classes}');
  foreach ($result as $class) {
    $options += [$class->pcid => $class->name];
  }

  return $options;
}

/**
 * Checks that the order has the selected combination of products.
 */
function uc_order_condition_has_products($order, $products, $required, $forbidden) {
  $order_products = [];
  foreach ($order->products as $product) {
    $order_products[] = $product->model;
  }
  $required_products = array_intersect($products, $order_products);
  if ($required) {
    $required_check = $required_products == $products;
  }
  else {
    $required_check = (bool) count($required_products);
  }
  if ($forbidden) {
    $forbidden_products = array_diff($order_products, $products);
    $forbidden_check = (bool) count($forbidden_products);
  }
  else {
    $forbidden_check = FALSE;
  }
  return $required_check && !$forbidden_check;
}

/**
 * Options callback.
 *
 * @see uc_order_condition_has_products()
 */
function uc_order_condition_has_products_options() {
  $options = [];
  $result = db_query('SELECT nid FROM {uc_products}');
  foreach ($result as $row) {
    $options += uc_product_get_models($row->nid, FALSE);
  }
  asort($options);

  return $options;
}

/**
 * Checks that the order has the selected number of products.
 *
 * @see uc_order_condition_count_products_form()
 */
function uc_order_condition_count_products($order, $products, $count, $op) {
  $totals = ['all' => 0];
  $total = 0;
  foreach ($order->products as $product) {
    $totals['all'] += $product->qty;
    if (isset($totals[$product->nid])) {
      $totals[$product->nid] += $product->qty;
    }
    else {
      $totals[$product->nid] = $product->qty;
    }
  }
  if (in_array('all', $products)) {
    $total = $totals['all'];
  }
  else {
    foreach ($products as $product) {
      if (isset($totals[$product])) {
        $total += $totals[$product];
      }
    }
  }
  return uc_order_condition_value_operator_comparison($total, $op, $count);
}

/**
 * Product options callback.
 */
function uc_order_condition_products_options() {
  $options = ['all' => t('- All products -')];
  $options += db_query('SELECT nid, model FROM {uc_products} ORDER BY model')->fetchAllKeyed();

  return $options;
}

/**
 * Operator options callback.
 */
function uc_order_condition_value_operator_options() {
  return [
    'less' => t('Total is less than specified value.'),
    'less_equal' => t('Total is less than or equal to specified value.'),
    'equal' => t('Total is equal to specified value.'),
    'greater_equal' => t('Total is greater than or equal to specified value.'),
    'greater' => t('Total is greater than specified value.'),
  ];
}

/**
 * Checks the weight of the order's products.
 *
 * @see uc_order_condition_products_weight_form()
 */
function uc_order_condition_products_weight($order, $products, $weight_units, $weight_value, $op) {
  $totals = ['all' => 0];
  $total = 0;
  foreach ($order->products as $product) {
    $unit_conversion = uc_weight_conversion($product->weight_units, $weight_units);
    $totals['all'] += $product->qty * $product->weight * $unit_conversion;
    $totals[$product->nid] = $product->qty * $product->weight * $unit_conversion;
  }
  if (in_array('all', $products)) {
    $total = $totals['all'];
  }
  else {
    foreach ($products as $product) {
      if (isset($totals[$product])) {
        $total += $totals[$product];
      }
    }
  }
  return uc_order_condition_value_operator_comparison($total, $op, $weight_value);
}

/**
 * Weight units options callback.
 */
function uc_order_condition_weight_units_options() {
  return [
    'lb' => t('Pounds'),
    'kg' => t('Kilograms'),
    'oz' => t('Ounces'),
    'g' => t('Grams'),
  ];
}

/**
 * Checks that the order is shippable.
 */
function uc_order_condition_is_shippable($order, $settings) {
  return $order->isShippable();
}

/**
 * Updates an order's status.
 *
 * @see uc_order_action_update_status_form()
 */
function uc_order_action_update_status($order, $status) {
  $order->setStatusId($status)->save();
}

/**
 * Adds a comment to an order.
 *
 * @see uc_order_action_add_comment_form()
 */
function uc_order_action_add_comment($order, $comment, $comment_type) {
  uc_order_comment_save($order->id(), 0,
    \Drupal::token()->replace($comment, ['uc_order' => $order]),
    $comment_type == 'admin' ? 'admin' : 'order',
    $order->getStatusId(), $comment_type == 'notified');
}

/**
 * @see uc_order_action_add_comment()
 */
function uc_order_action_order_comment_types() {
  return [
    'admin' => t('Enter this as an admin comment.'),
    'order' => t('Enter this as a customer order comment.'),
    'notified' => t('Enter this as a customer order comment with a notified icon.'),
  ];
}

/**
 * Sends an email concerning an order.
 *
 * The 'Sender', 'Recipients', 'Subject', and 'Message' fields accept
 * order token replacements.
 *
 * @see uc_order_action_email_form()
 */
function uc_order_action_email($order, $from, $addresses, $subject, $message, $format) {
  $token_service = \Drupal::token();

  $settings = [
    'from' => $from,
    'addresses' => $addresses,
    'subject' => $subject,
    'message' => $message,
    'format' => $format,
  ];

  // Token replacements for the subject and body.
  $settings['replacements'] = [
    'uc_order' => $order,
  ];

  // Apply token replacements to the 'from' e-mail address.
  $from = $token_service->replace($settings['from'], $settings['replacements']);
  if (empty($from)) {
    $from = uc_store_email_from();
  }

  // Apply token replacements to 'recipient' e-mail addresses.
  $addresses = $token_service->replace($settings['addresses'], $settings['replacements']);
  // Split up our recipient e-mail addresses.
  $recipients = [];
  foreach (explode("\n", $addresses) as $address) {
    $address = trim($address);
    // Remove blank lines.
    if (!empty($address)) {
      $recipients[] = $address;
    }
  }

  foreach ($recipients as $email) {
    $sent = \Drupal::service('plugin.manager.mail')->mail('uc_order', 'rules-action-email', $email, uc_store_mail_recipient_langcode($email), $settings, $from);

    if (!$sent['result']) {
      \Drupal::logger('uc_order')->error('Attempt to e-mail @email concerning order @order_id failed.', ['@email' => $email, '@order_id' => $order->id()]);
    }
  }
}

/**
 * Options list callback for message formats.
 */
function uc_order_message_formats() {
  global $user;

  $options = [];
  $formats = filter_formats($user);
  foreach ($formats as $format) {
    $options[$format->format] = $format->name;
  }

  return $options;
}

/**
 * Emails an invoice.
 *
 * The 'Sender', 'Subject' and 'Addresses' fields take order token replacements.
 */
function uc_order_action_email_invoice($order, $from, $addresses, $subject, $template, $view) {
  $token_service = \Drupal::token();

  $settings = [
    'from' => $from,
    'addresses' => $addresses,
    'subject' => $subject,
    'template' => $template,
    'view' => $view,
  ];
  // Token replacements for the from, subject and body.
  $settings['replacements'] = [
    'uc_order' => $order,
  ];

  // Apply token replacements to the 'from' e-mail address.
  $from = $token_service->replace($settings['from'], $settings['replacements']);
  if (empty($from)) {
    $from = uc_store_email_from();
  }

  // Apply token replacements to 'recipient' e-mail addresses.
  $addresses = $token_service->replace($settings['addresses'], $settings['replacements']);
  // Split up our recipient e-mail addresses.
  $recipients = [];
  foreach (explode("\n", $addresses) as $address) {
    $address = trim($address);
    // Remove blank lines.
    if (!empty($address)) {
      $recipients[] = $address;
    }
  }

  $message = [
    '#theme' => 'uc_order_invoice',
    '#order' => $order,
    '#op' => $settings['view'],
    '#template' => $settings['template'],
  ];
  $settings['message'] = \Drupal::service('renderer')->renderPlain($message);

  foreach ($recipients as $email) {
    $sent = \Drupal::service('plugin.manager.mail')->mail('uc_order', 'rules-action-email', $email, uc_store_mail_recipient_langcode($email), $settings, $from);

    if (!$sent['result']) {
      \Drupal::logger('uc_order')->error('Attempt to e-mail invoice for order @order_id to @email failed.', ['@email' => $email, '@order_id' => $order->id()]);
    }
  }
}

/**
 * @see uc_order_action_email_invoice()
 */
function uc_order_action_email_invoice_view_options() {
  return [
    'print' => t('Show the business header and shipping method.'),
    'admin-mail' => t('Show all of the above plus the help text, email text, and store footer.'),
    'checkout-mail' => t('Show all of the above plus the "thank you" message.'),
  ];
}

/**
 * Value comparison.
 *
 * @param float $source
 *   The source value.
 * @param string $op
 *   The comparison operator.
 * @param float $target
 *   The target value.
 *
 * @return bool
 *   Whether the comparison meets the specified conditions.
 *
 * @see uc_order_condition_value_operator_options
 */
function uc_order_condition_value_operator_comparison($source, $op, $target) {
  switch ($op) {
    case 'less':
      return $source < $target;

    case 'less_equal':
      return $source <= $target;

    case 'equal':
      return $source == $target;

    case 'greater_equal':
      return $source >= $target;

    case 'greater':
      return $source > $target;
  }
}

/**
 * Compare order total.
 *
 * @param object $order
 *   The order to check.
 * @param string $op
 *   The comparison operator.
 * @param float $value
 *   The target value.
 *
 * @return bool
 *   Whether the order total meets the specified condition.
 *
 * @see uc_order_condition_value_operator_options
 */
function uc_order_condition_total($order, $op, $value) {
  return uc_order_condition_value_operator_comparison($order->order_total, $op, $value);
}

/**
 * Compare order subtotal.
 *
 * @param object $order
 *   The order to check.
 * @param string $op
 *   The comparison operator.
 * @param float $value
 *   The target value.
 *
 * @return bool
 *   Whether the order subtotal meets the specified condition.
 *
 * @see uc_order_condition_value_operator_options
 */
function uc_order_condition_subtotal($order, $op, $value) {
  if (is_array($order->line_items)) {
    foreach ($order->line_items as $line_item) {
      if ($line_item['type'] == 'subtotal') {
        $subtotal = $line_item['amount'];
        return uc_order_condition_value_operator_comparison($subtotal, $op, $value);
      }
    }
  }
  return FALSE;
}

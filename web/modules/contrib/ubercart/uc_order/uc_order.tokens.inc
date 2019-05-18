<?php

/**
 * @file
 * Token hooks for the uc_order module.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
function uc_order_token_info() {
  $types = [
    'name' => t('Orders'),
    'description' => t('Tokens related to Ubercart orders.'),
    'needs-data' => 'uc_order',
  ];

  $tokens = [];

  $tokens['new-username'] = [
    'name' => t('New username'),
    'description' => t('New username associated with an order if applicable.'),
  ];
  $tokens['new-password'] = [
    'name' => t('New password'),
    'description' => t('New password associated with an order if applicable.'),
  ];
  $tokens['order-number'] = [
    'name' => t('Order number'),
    'description' => t('The unique identifier of the order.'),
  ];
  $tokens['url'] = [
    'name' => t('URL'),
    'description' => t('The URL to the order'),
    'type' => 'url',
  ];
  $tokens['link'] = [
    'name' => t('Link'),
    'description' => t('A link to the order using the order ID.'),
  ];
  $tokens['admin-url'] = [
    'name' => t('Admin URL'),
    'description' => t('The URL to the admin view page using the order ID.'),
    'type' => 'url',
  ];
  $tokens['admin-link'] = [
    'name' => t('Admin link'),
    'description' => t('A link to the order admin view page using the order ID.'),
  ];
  $tokens['subtotal'] = [
    'name' => t('Subtotal'),
    'description' => t('The subtotal of products on an order.'),
  ];
  $tokens['total'] = [
    'name' => t('Total'),
    'description' => t('The order total.'),
  ];
  $tokens['email'] = [
    'name' => t('Email'),
    'description' => t('The primary e-mail address of the order.'),
  ];
  // Duplicate the [uc_order:email] token as [uc_order:mail] because the Views
  // Bulk Operations module validates tokens ending in :mail.
  $tokens['mail'] = $tokens['email'];
  $tokens['shipping-method'] = [
    'name' => t('Shipping method'),
    'description' => t('The title of the first shipping line item.'),
  ];
  $tokens['shipping-address'] = [
    'name' => t('Shipping address'),
    'description' => t('The order shipping address.'),
  ];
  $tokens['shipping-phone'] = [
    'name' => t('Shipping phone number'),
    'description' => t('The phone number for the shipping address.'),
  ];
  $tokens['billing-address'] = [
    'name' => t('Billing address'),
    'description' => t('The order billing address.'),
  ];
  $tokens['billing-phone'] = [
    'name' => t('Billing phone number'),
    'description' => t('The phone number for the billing address.'),
  ];
  $tokens['first-name'] = [
    'name' => t("Customer's first name"),
    'description' => t('The first name associated with the order.'),
  ];
  $tokens['last-name'] = [
    'name' => t("Customer's last name"),
    'description' => t('The last name associated with the order.'),
  ];
  $tokens['comments'] = [
    'name' => t('Comments'),
    'description' => t('Comments left by the customer.'),
  ];
  $tokens['last-comment'] = [
    'name' => t('Last comment'),
    'description' => t('Last order comment left by an administrator (not counting the order admin comments).'),
  ];
  $tokens['order-status'] = [
    'name' => t('Order status'),
    'description' => t('The current order status.'),
  ];

  $tokens['customer'] = [
    'name' => t('Customer'),
    'description' => t('The user associated with the order.'),
    'type' => 'user',
  ];
  $tokens['created'] = [
    'name' => t('Date created'),
    'description' => t('The date and time when the order was created.'),
    'type' => 'date',
  ];
  $tokens['changed'] = [
    'name' => t('Date changed'),
    'description' => t('The date the order was most recently updated.'),
    'type' => 'date',
  ];

  $tokens['products'] = [
    'name' => t('Products'),
    'description' => t('A list of products in the order.'),
  ];

  return [
    'types' => ['uc_order' => $types],
    'tokens' => ['uc_order' => $tokens],
  ];
}

/**
 * Implements hook_tokens().
 */
function uc_order_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  if (isset($options['langcode'])) {
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }

  $replacements = [];

  if ($type == 'uc_order' && !empty($data['uc_order'])) {
    /** @var \Drupal\uc_order\OrderInterface $order */
    $order = $data['uc_order'];
    $path = 'user/' . $order->getOwnerId() . '/orders/' . $order->id();
    $admin_path = 'admin/store/orders/' . $order->id();

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'new-username':
          if (isset($order->data->new_user_name)) {
            $replacements[$original] = $order->data->new_user_name;
          }
          break;

        case 'new-password':
          $replacements[$original] = isset($order->password) ? $order->password : t('Your password');
          break;

        case 'order-number':
          $replacements[$original] = $order->id();
          break;

        case 'url':
          $replacements[$original] = Url::fromUri('internal:/' . $path, ['absolute' => TRUE])->toString();
          break;

        case 'link':
          $replacements[$original] = Link::fromTextAndUrl($order->id(), Url::fromUri('internal:/' . $path, ['absolute' => TRUE]))->toString();
          break;

        case 'admin-url':
          $replacements[$original] = Url::fromUri('internal:/' . $admin_path, ['absolute' => TRUE])->toString();
          break;

        case 'admin-link':
          $replacements[$original] = Link::fromTextAndUrl($order->id(), Url::fromUri('internal:/' . $admin_path, ['absolute' => TRUE]))->toString();
          break;

        case 'subtotal':
          $subtotal = '';
          if (is_array($order->line_items)) {
            foreach ($order->line_items as $key => $value) {
              if ($value['type'] == 'subtotal') {
                $subtotal = uc_currency_format($order->line_items[$key]['amount']);
              }
            }
          }
          $replacements[$original] = $subtotal;
          break;

        case 'subtotal-value':
          if (is_array($order->line_items)) {
            foreach ($order->line_items as $key => $value) {
              if ($value['type'] == 'subtotal') {
                $subtotal = $order->line_items[$key]['amount'];
              }
            }
          }
          $replacements[$original] = $subtotal;
          break;

        case 'total':
          $replacements[$original] = uc_currency_format($order->getTotal());
          break;

        case 'total-value':
          $replacements[$original] = $order->getTotal();
          break;

        case 'email':
        case 'mail':
          $replacements[$original] = $order->getEmail();
          break;

        case 'shipping-method':
          if (is_array($order->line_items)) {
            foreach ($order->line_items as $value) {
              if ($value['type'] == 'shipping' && !isset($ship_method)) {
                $ship_method = $value['title'];
                break;
              }
            }
          }
          $replacements[$original] = !isset($ship_method) ? t('Standard delivery') : $ship_method;
          break;

        case 'shipping-address':
          // Cast Address object to string to get country-specific formatting.
          $address = ['#markup' => (string) $order->getAddress('delivery')];
          $replacements[$original] = drupal_render($address);
          break;

        case 'shipping-phone':
          $replacements[$original] = $order->getAddress('delivery')->getPhone();
          break;

        case 'billing-address':
          // Cast Address object to string to get country-specific formatting.
          $address = ['#markup' => (string) $order->getAddress('billing')];
          $replacements[$original] = drupal_render($address);
          break;

        case 'billing-phone':
          $replacements[$original] = $order->getAddress('billing')->getPhone();
          break;

        case 'first-name':
          if (\Drupal::config('uc_store.settings')->get('customer_address') == 'shipping') {
            $replacements[$original] = $order->getAddress('delivery')->getFirstName();
          }
          else {
            $replacements[$original] = $order->getAddress('billing')->getFirstName();
          }
          break;

        case 'last-name':
          if (\Drupal::config('uc_store.settings')->get('customer_address') == 'shipping') {
            $replacements[$original] = $order->getAddress('delivery')->getLastName();
          }
          else {
            $replacements[$original] = $order->getAddress('billing')->getLastName();
          }
          break;

        case 'comments':
          $result = db_query_range("SELECT message FROM {uc_order_comments} WHERE order_id = :order_id AND uid = :uid ORDER BY created DESC", 0, 1, [':order_id' => $order->id(), ':uid' => 0])->fetchField();
          $replacements[$original] = empty($result) ? t('<i>No comments left.</i>') : $result;
          break;

        case 'last-comment':
          $result = db_query_range("SELECT message FROM {uc_order_comments} WHERE order_id = :order_id AND uid > :uid ORDER BY created DESC", 0, 1, [':order_id' => $order->id(), ':uid' => 0])->fetchField();
          $replacements[$original] = empty($result) ? t('<i>No comment found.</i>') : $result;
          break;

        case 'order-status':
          $replacements[$original] = $order->getStatus()->getName();
          break;

        case 'customer':
          $replacements[$original] = $order->getOwnerId();
          break;

        case 'created':
          $date_format = DateFormat::load('short');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($order->getCreatedTime(), 'short', '', NULL, $langcode);
          break;

        case 'changed':
          $date_format = DateFormat::load('short');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($order->getChangedTime(), 'short', '', NULL, $langcode);
          break;

        case 'products':
          $products = [];
          foreach ($order->products as $product) {
            $qty = [
              '#theme' => 'uc_qty',
              '#qty' => $product->qty->value,
            ];
            $products[] = drupal_render($qty) . ' ' . $product->title->value;
          }
          $replacements[$original] = implode("<br />\n", $products);
          break;
      }
    }

    // Handles chaining for tokens that have 'type' defined in hook_token_info()
    if ($link_tokens = $token_service->findWithPrefix($tokens, 'url')) {
      $replacements += $token_service->generate('url', $link_tokens, ['path' => $path], $options, $bubbleable_metadata);
    }

    if ($link_tokens = $token_service->findWithPrefix($tokens, 'admin-url')) {
      $replacements += $token_service->generate('url', $link_tokens, ['path' => $admin_path], $options, $bubbleable_metadata);
    }

    if ($customer_tokens = $token_service->findWithPrefix($tokens, 'customer')) {
      $replacements += $token_service->generate('user', $customer_tokens, ['user' => $order->getOwner()], $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $order->getCreatedTime()], $options, $bubbleable_metadata);
    }

    if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $changed_tokens, ['date' => $order->getChangedTime()], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}

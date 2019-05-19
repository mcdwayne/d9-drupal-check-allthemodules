<?php

namespace Drupal\uc_gc_client\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_order\Entity\Order;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\uc_gc_client\Plugin\Ubercart\PaymentMethod\GoCardlessClient;

/**
 * Returns responses for GoCardless routes.
 */
class GoCardless extends ControllerBase {

  /**
   * Handles webhooks from GC.
   */
  public function GoCardlessWebhook() {

    $settings = GoCardlessPartner::getSettings();
    $secret = GoCardlessPartner::getPartnerWebhook();
    $webhook = file_get_contents('php://input');

    //Required for nginx servers.
    if (!function_exists('getallheaders')) {
      function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
          if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
          }
        }
        return $headers;
      }
    }

    $headers = getallheaders();
    $provided_signature = $headers["Webhook-Signature"];
    $calculated_signature = hash_hmac("sha256", $webhook, $secret);
    if ($provided_signature == $calculated_signature) {

      $data = json_decode($webhook, TRUE);

      // Optionally write webhook to log.
      if ($settings['debug_webhook']) {
        \Drupal::logger('uc_gc_client')->notice('<pre>GoCardless webhook: <br />' . print_r($data, TRUE) . '</pre>');
      }

      // Process the events.
      foreach ($data['events'] as $event) {

        switch ($event['resource_type']) {

          case 'mandates':
            $order_id = uc_gc_client_id($event['links']['mandate']);
            $order = Order::load($order_id);
            $resource = [];
            GoCardless::mandate($order, $event['action'], $event);
            break;

          case 'subscriptions':
            $resource = [];
            GoCardless::subscription($order, $event['action'], $event);
            break;

          case 'payments':
            $payment_id = $event['links']['payment'];
            $partner = new GoCardlessPartner();
            $result = $partner->api([
              'endpoint' => 'payments',
              'action' => 'get',
              'id' => $payment_id,
            ]);
            if ($result->response->status_code == 201) {
              $resource = $result->response->body->payments;
              $order_id = uc_gc_client_id($resource->links->mandate);
              $order = Order::load($order_id);
              GoCardless::payment($order, $event['action'], $event, $resource);
            }
            break;

        }

        // Provide hook so other modules can respond to GoCardless webhooks.
        $params = [
          'event' => $event,
          'resource' => $resource,
          'order_id' => $order_id,
        ];
        \Drupal::moduleHandler()->invokeAll('gc_client_webhook', [$params]);
      }

      // Send a success header.
      header('HTTP/1.1 200 OK');
    }
    else {
      header("HTTP/1.1 498 Invalid Token");
    }
    return new Response();
  }

  /**
   * Processes 'mandate' webhooks.
   */
  public function mandate($order, $action, $event) {

    switch ($action) {

      case 'submitted':
        if ($order->order_status->getString() <> 'processing') {
          $comment = t('Your direct debit mandate @mandate has been submitted to your bank by GoCardless and will be processed soon.', ['@mandate' => $event['links']['mandate']]);
          uc_order_comment_save($order->id(), $order->uid->getString(), $comment, 'order', 'processing', FALSE);
          $order->setStatusId('processing')->save();
          $update = db_update('uc_gc_client')
            ->fields([
              'status' => 'pending',
              'updated' => REQUEST_TIME,
            ])
            ->condition('ucid', $order->id())
            ->execute();
        }
        break;

      case 'failed':
        if ($order->order_status->getString() <> 'mandate_failed') {
          $comment = t('Your direct debit mandate @mandate creation has failed.', ['@mandate' => $event['links']['mandate']]);
          uc_order_comment_save($order->id(), $order->uid->getString(), $comment, 'order', 'processing', TRUE);
          $order->setStatusId('mandate_failed')->save();
        }
        break;

      case 'active':
        if ($order->order_status->getString() <> 'mandate_active') {
          $comment = t('Your direct debit mandate @mandate has been activated successfully with your bank.', ['@mandate' => $event['links']['mandate']]);
          uc_order_comment_save($order->id(), $order->uid->getString(), $comment, 'order', 'completed', TRUE);
          $order->setStatusId('mandate_active')->save();
          $update = db_update('uc_gc_client')
            ->fields([
              'status' => 'completed',
              'updated' => REQUEST_TIME,
            ])
            ->condition('ucid', $order->id())
            ->execute();
        }
        break;

      case 'cancelled':
        if ($order->order_status->getString() <> 'canceled') {
          $comment = t('Your direct debit mandate @mandate has been cancelled with your bank by GoCardless.', ['@mandate' => $event['links']['mandate']]);
          uc_order_comment_save($order->id(), $order->uid->getString(), $comment, 'order', 'canceled', TRUE);
          $order->setStatusId('canceled')->save();
          $update = db_update('uc_gc_client')
            ->fields([
              'status' => 'canceled',
              'updated' => REQUEST_TIME,
            ])
            ->condition('ucid', $order->id())
            ->execute();
        }
        break;

      case 'reinstated':
        if ($order->order_status->getString() <> 'processing') {
          $comment = t('Your direct debit mandate @mandate has been reinstated at GoCardless.', ['@mandate' => $event['links']['mandate']]);
          uc_order_comment_save($order->id(), $order->uid->getString(), $comment, 'order', 'processing', FALSE);
          $order->setStatusId('pending')->save();
          $update = db_update('uc_gc_client')
            ->fields([
              'status' => 'pending',
              'updated' => REQUEST_TIME,
            ])
            ->condition('ucid', $order->id())
            ->execute();
        }
        break;
    }
  }

  /**
   * Processes 'payment' webhooks.
   */
  public function payment($order, $action, $event, $resource) {

    $mandate = $resource->links->mandate;
    $amount = $resource->amount / 100;
    !empty($order->billing_country->getString()) ? $country_code = $order->billing_country->getString() : $country_code = $order->delivery_country->getString();
    $currency_sign = uc_gc_client_currency($country_code)['sign'];

    switch ($action) {

      case 'confirmed':
        uc_payment_enter($order->id(), 'gc_client', $amount, 0, NULL, t('Direct debit has been taken by GoCardless'));
        $comment = t('Your payment of @amount has been confirmed by GoCardless and will be paid from your bank account.', ['@amount' => uc_currency_format($amount, $currency_sign)]);
        uc_order_comment_save($order->id(), 0, $comment, 'order', $order->order_status->getString(), TRUE);
        // Update status to payment_received if it is the first one.
        if ($order->order_status->getString() == 'mandate_active') {
          $order->setStatusId('payment_received')->save();
        }
        break;

      case 'cancelled':
        $comment = t("Your direct debit payment '@id' for @amount has been cancelled at GoCardless.", ['@id' => $event['id'], '@amount' => uc_currency_format($amount, $currency_sign)]);
        uc_order_comment_save($order->id, 0, $comment, 'order', $order->order_status->getString(), TRUE);
        break;
    }
  }

  /**
   * Processes 'subscription' webhooks.
   */
  public function subscription($order, $action, $event) {
    /*
    switch ($action) {

    case 'cancelled' :
    foreach ($items as $item) {
    isset($item['source_id']) ? $gc_order_id = $item['source_id'] : $gc_order_id = $item['id'];
    $order_id = uc_gc_client_id($gc_order_id);
    $order = uc_order_load($order_id);
    uc_order_update_status($order_id, 'canceled');
    uc_order_comment_save($order_id, $order->uid, t('This direct debit Subscription has been cancelled with GoCardless.com.'), 'order', 'canceled', TRUE);
    // update the status on the database
    $update = db_update('uc_gcsubs')
    ->fields(array(
    'status' => 'canceled',
    'updated' => time(),
    ))
    ->condition('ucid', $order_id, '=')
    ->execute();
    }

    // Invoke Rules event
    //if (module_exists('rules')) {
    //  $items_string = json_encode($items);
    //  rules_invoke_event('uc_gcsubs_subs_cancellation', $items_string);
    //}
    break;
    }
     */
  }

  /**
   * Handles a complete GoCardless sale.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the cart or checkout complete page.
   */
  public function GoCardlessComplete($redirect, $mandate, $customer, $order_id, $start_date = NULL, $cart_id = 0) {

    $order = Order::load($order_id);
    $uid = $order->getOwnerId();

    // Ensure the payment method is GoCardless.
    $method = \Drupal::service('plugin.manager.uc_payment.method')->createFromOrder($order);

    if (!$method instanceof GoCardlessClient) {
      return $this->redirect('uc_cart.cart');
    }

    // Let customer know mandate has been created.
    $comment = t('Your new direct debit mandate @mandate has been created by GoCardless.', ['@mandate' => $mandate]);
    uc_order_comment_save($order_id, $uid, $comment, 'order', 'pending', TRUE);
    drupal_set_message($comment);

    $uc_store_settings = \Drupal::config('uc_store.settings');

    // @TODO Apply some logic here to group products into single
    //  payments / subscriptions if applicable

    // Set up seperate payments / subscriptions for each product in cart.
    foreach ($order->products as $product) {

      $ucpid = $product->order_product_id->value;
      $data = $product->data->getValue()[0];
      $data['gc_auth_type'] == 'subscription' ? $gc_type = 'S' : $gc_type = 'P';

      $product_uc = db_select('uc_gc_client_products', 'p')
        ->fields('p')
        ->condition('nid', $product->nid->getString())
        ->execute()->fetch();

      // Obtain initial payment creation date.

      // Use start date.
      if ($product_uc->start_date) {
        if (strtotime($product_uc->start_date) < REQUEST_TIME) {
          $start_date = REQUEST_TIME;
        }
        else $start_date = strtotime($product_uc->start_date);
      }

      // Use day(s) of month.
      elseif ($product_uc->dom) {
        $doms = explode(',', $product_uc->dom);
        $dates = [];
        foreach ($doms as $dom) {
          $dom >= date('d') ? $month = 'last' : $month = 'this';
          $time = strtotime('+' . $dom . ' days', strtotime('last day of ' . $month . ' month'));
          $dates[] = $time;
        }
        sort($dates);
        $start_date = array_shift($dates);
      }

      // Create payment immidiately.
      elseif ($product_uc->create_payment) {
        $start_date = REQUEST_TIME;
      }

      // Provide hook so initial date can be provided by another module.
      elseif (is_null($start_date)) {
        \Drupal::moduleHandler()->alter('gc_client_start_date', $start_date, $product);
      }

      // Insert info about the order into the database.
      $insert_fields = [
        'ucid'   => $order_id,
        'ucpid'  => $ucpid,
        'gcid'   => $mandate,
        'gcrid'  => $redirect,
        'gccid'  => $customer,
        'uid'    => $uid,
        'type'   => $gc_type,
        'status' => 'pending',
        'created' => REQUEST_TIME,
        'start_date' => $start_date,
        'updated' => REQUEST_TIME,
        'sandbox' => $method->getConfiguration()['sandbox'],
      ];
      $update_fields = [
        'updated' => REQUEST_TIME,
      ];
      db_merge('uc_gc_client')
        ->key(['ucpid' => $ucpid])
        ->insertFields($insert_fields)
        ->updateFields($update_fields)
        ->execute();

      isset($data['interval_params']) ? $interval = $data['interval_params'] : NULL;
      $currency_code = $uc_store_settings->get('currency')['code'];
      $currency_sign = $uc_store_settings->get('currency')['symbol'];

      // Subscription payments.
      if ($gc_type == 'S') {

        $calculate = uc_gc_client_price_calculate($order, $ucpid);
        if (isset($calculate['currency'])) {
          $currency_code = $calculate['currency'];
        }
        if (isset($calculate['sign'])) {
          $currency_sign = $calculate['sign'];
        }

        $payment_details = [
          'type' => 'subscription',
          'amount' => $calculate['amount'],
          'currency' => $currency_code,
          'name' => 'Subscription plan for ' . $product->title->value,
          'interval' => $interval['length'],
          'interval_unit' => $interval['unit_gc'],
          'metadata' => [
            'ucpid' => $ucpid,
          ],
        ];

        // Provide hook so payment details can be altered by another module
        // before the payment is created with GC.
        \Drupal::moduleHandler()->alter('gc_client_subs_payment', $payment_details, $order);

        $params = [
          'endpoint' => 'subscriptions',
          'action' => 'create',
          'mandate' => $mandate,
          'amount' => $payment_details['amount'],
          'currency' => $payment_details['currency'],
          'name' => $payment_details['name'],
          'interval' => $payment_details['interval'],
          'interval_unit' => $payment_details['interval_unit'],
          'metadata' => $payment_details['metadata'],
        ];
        if (!isset($product_uc->create_payment)) {
          $params['start_date'] = format_date($start_date, 'gocardless');
        }
        if (!isset($partner)) {
          $partner = new GoCardlessPartner();
        }
        $result = $partner->api($params);

        if ($result->response->status_code == 201) {

          $sub = $result->response->body->subscriptions;
          $comment_arr = [
            '@product' => $product->title->value,
            '@interval' => $sub->interval,
            '@interval_unit' => $sub->interval_unit,
            '@amount' => uc_currency_format($sub->amount / 100, $currency_sign),
            '@start_date' => format_date(strtotime($sub->start_date), 'uc_store'),
          ];

          $comment = t('Your @interval @interval_unit subscription plan of @amount for @product has been created with GoCardless, and the first payment will be made from your bank on @start_date.', $comment_arr);
          uc_order_comment_save($order_id, $uid, $comment, 'order', 'pending', TRUE);
          drupal_set_message($comment);
        }
        else {
          drupal_set_message(t('Something went wrong creating your subscription with GoCardless. Please contact the site administrator for assistance.'), 'warning');
        }
      }

      // One-off payments.
      elseif ($gc_type == 'P') {

        // Create the first payment immediately.
        if (!is_null($start_date) && $start_date <= REQUEST_TIME) {      

          $calculate = uc_gc_client_price_calculate($order, $ucpid);
          if (isset($calculate['currency'])) {
            $currency_code = $calculate['currency'];
          }
          if (isset($calculate['sign'])) {
            $currency_sign = $calculate['sign'];
          }
          $order->save();

          $payment_details = [
            'type' => 'one-off payment',
            'amount' => $calculate['amount'],
            'currency' => $currency_code,
            'description' => 'Initial payment for ' . $product->title->value,
            'metadata' => [
              'ucpid' => $ucpid,
            ],
          ];

          // Provide hook so payment details can be altered by another module
          // before the payment is created with GC.
          \Drupal::moduleHandler()->alter('gc_client_payments_payment', $payment_details, $order);

          if (!isset($partner)) {
            $partner = new GoCardlessPartner();
          }
          $result = $partner->api([
            'endpoint' => 'payments',
            'action' => 'create',
            'mandate' => $mandate,
            'amount' => $payment_details['amount'],
            'currency' => $payment_details['currency'],
            'description' => $payment_details['description'],
            'metadata' => $payment_details['metadata'],
          ]);

          if ($result->response->status_code == 201) {
            // Update next_payment field in gc_client table.
            $payment = $result->response->body->payments;

            isset($interval) ? $next_payment = strtotime('+' . $interval['string']) : $next_payment = NULL;

            $update = db_update('uc_gc_client')
              ->fields([
                'next_payment' => $next_payment,
                'updated' => REQUEST_TIME,
              ])
              ->condition('ucpid', $ucpid)
              ->execute();

            // Let everyone know what is going on.
            $comment_array = [
              '@amount' => uc_currency_format($payment->amount / 100, $currency_sign),
              '@charge_date' => date('D d M Y', strtotime($result->response->body->payments->charge_date)),
              '@product' => $product->title->value,
            ];
            $comment = t('An initial payment of @amount for @product has been created with GoCardless, and will be made from your bank on @charge_date.', $comment_array);
            uc_order_comment_save($order_id, $uid, $comment, 'order', 'pending', TRUE);
            drupal_set_message($comment);
          }

          else {
            drupal_set_message(t('An initial payment could not be created due to an unknown error. We will try and raise it again later.'), 'error');

            // Update next_payment to now so that it will get picked up on next
            // cron run.
            $update = db_update('uc_gc_client')
              ->fields([
                'next_payment' => REQUEST_TIME,
                'updated' => REQUEST_TIME,
              ])
              ->condition('ucpid', $ucpid)
              ->execute();
          }
        }

        // Else if a start date is set for the product then defer the
        // first payment creation.
        elseif (!is_null($start_date)) {

          // Update next_payment field in uc_gcsubs table.
          $update = db_update('uc_gc_client')
            ->fields([
              'next_payment' => $start_date,
              'updated' => REQUEST_TIME,
            ])
            ->condition('ucpid', $ucpid)
            ->execute();

          $calculate = uc_gc_client_price_calculate($order, $ucpid);
          if (isset($calculate['currency'])) {
            $currency_code = $calculate['currency'];
          }
          if (isset($calculate['sign'])) {
            $currency_sign = $calculate['sign'];
          }
          $order->save();

          // Let everyone know what is going on.
          $comment = t('A payment for @amount will be created with GoCardless on @start_date.', ['@amount' => uc_currency_format($calculate['amount'], $currency_sign), '@start_date' => format_date($start_date, 'uc_store')]);
          uc_order_comment_save($order_id, $uid, $comment, 'order', 'pending', TRUE);
          drupal_set_message($comment);
        }
      }
    }

    // This lets us know it's a legitimate access of the complete page.
    $session = \Drupal::service('session');
    $session->set('uc_checkout_complete_' . $order_id, TRUE);

    return $this->redirect('uc_cart.checkout_complete');
  }

  /**
   * Callback function.
   *
   * Deletes GoC payments if status is pending_submission.
   */
  public function cancelPayment($ucid, $payment_id) {

    $partner = new GoCardlessPartner();
    $result = $partner->api([
      'endpoint' => 'payments',
      'action' => 'cancel',
      'id' => $payment_id,
    ]);

    if ($result->response->status_code == 200) {
      drupal_set_message(t('Payment @payment_id is cancelled', ['@payment_id' => $payment_id]));
    }
    else {
      drupal_set_message(t('There was a problem cancelling payment @payment_id', ['@payment_id' => $payment_id]), 'warning');
    }

    return new RedirectResponse('/admin/store/orders/' . $ucid . '/gocardless');
  }

  /**
   * Cancel or delete scheduled adjustment
   */
  public function adjustAction($action, $ucid, $sid) {

    if ($action == 'cancel') {
      $update = db_update('uc_gc_client_schedules')
        ->fields([
          'status' => 0,
        ])
        ->condition('sid', $sid)
        ->execute();
    }

    if ($action == 'delete') {

      $delete_sch = db_delete('uc_gc_client_schedules')
        ->condition('sid', $sid)
        ->execute();
    }

    $action == 'cancel' ? $action = 'cancelled' : $action = 'deleted';
    drupal_set_message(t('Scheduled adjustment @action.', ['@action' => $action]));
    return new RedirectResponse('/admin/store/orders/' . $ucid . '/gocardless');
  }
}

<?php

namespace Drupal\uc_gc_client\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_gc_client\Controller\GoCardlessPartner;
use Drupal\uc_order\Entity\Order;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Component\Render\FormattableMarkup;

/**
 *
 */
class PaymentsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_gc_client_payments_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL) {

    $gcs = db_select('uc_gc_client', 'g')
      ->fields('g')
      ->condition('ucid', $uc_order->id())
      ->execute()->fetchAllAssoc('ucpid');
    $count = count($gcs);

    if ($count == 0) {
      $no_gcs = 'You have no GoCardless payments associated with this order.';
      $form['no_orders'] = [
        '#type' => 'item',
        '#markup' => $no_gcs,
      ];
      return $form;
    }

    // Create an array of unique product names.
    $rows = [];
    $product_select = [];
    $title_count = [];
    foreach ($uc_order->products as $product_id => $product) {
      !isset($title_count[$product->title->value]) ? $title_count[$product->title->value] = 1 : $title_count[$product->title->value]++;
      $title_count[$product->title->value] == 1 ? $title[$product_id] = $product->title->value : $title[$product_id] = $product->title->value . ' (' . $title_count[$product->value->title] . ')';
      $product_select[$product_id] = $title[$product_id];
    }

    if ($count == 1) {

      $ucpid = reset($gcs)->ucpid;
      $text_top = t('<h3><b>Administrate <a href = "@url">@title</a></b></h3>', ['@title' => $product_select[$ucpid], '@url' => 'admin/store/orders/' . $uc_order->id()]);
      $form['text_top'] = [
        '#type' => 'item',
        '#markup' => $text_top,
      ];
      $form['ucpid'] = [
        '#type' => 'value',
        '#value' => $ucpid,
      ];
    }
    else {

      if (isset($form_state->getUserInput()['ucpid'])) {
        $ucpid = $form_state->getUserInput()['ucpid'];
      }
      elseif (isset($_SESSION['change_ucpid_' . $uc_order->id()])) {
        $ucpid = $_SESSION['change_ucpid_' . $uc_order->id()]['ucpid'];
      }
      else {
        $ucpid = reset($gcs)->ucpid;
      }

      $form = ['#attributes' => ['id' => ['change-product']]];

      $text_top = t('<h3><b>Administrate <a href = "@url">@title</a></b></h3>', ['@title' => $product_select[$ucpid], '@url' => 'admin/store/orders/' . $uc_order->id()]);

      $form['text_top'] = [
        '#type' => 'item',
        '#markup' => $text_top,
      ];

      $form['ucpid'] = [
        '#title' => t('Switch product'),
        '#type' => 'select',
        '#options' => $product_select,
        '#default_value' => $ucpid,
        '#ajax' => [
          'callback' => '::switchProduct',
          'wrapper' => 'change-product',
          'method' => 'replace',
        ],
      ];
    }

    $gc = $gcs[$ucpid];

    $partner = new GoCardlessPartner();
    $result = $partner->api([
      'endpoint' => 'mandates',
      'action' => 'get',
      'mandate' => $gc->gcid,
    ]);

    if (!isset($result->response)) {
      drupal_set_message(t('No active  mandate found for this order at GoCardless'), 'warning');
      return;
    }
    else {
      $mandate = $result->response->body->mandates;
    }

    if ($mandate->scheme == 'bacs') {
      $sign = '£';
    }
    elseif ($mandate->scheme == 'sepa_core') {
      $sign = '€';
    }
    else {
      $sign = 'kr';
    }
    $product = $uc_order->products[$ucpid];
    $interval_params = $product->data->getValue()[0]['interval_params'];
    $interval = $interval_params['length'] . ' ' . $interval_params['unit'];

    if ($gc->type == 'P') {

      // Payments.
      $type = 'One-off payments';
      $header = ['product', 'payment type', 'cost', 'price', 'payment interval', 'next scheduled payment creation', 'next possible charge date', 'gocardless id', 'created', 'status'];

      $payment_header = ['Payment name', 'Payment ID', 'Amount', 'Created at', 'Status', 'Charge customer at', ''];

      $rows[$product->id()] = [
        $product_select[$ucpid],
        $type,
        uc_currency_format($product->cost->value),
        uc_currency_format($product->price->value),
        $interval == ' ' ? 'Not set' : $interval,
        $mandate->status != 'cancelled' && !is_null($gc->next_payment) ? format_date($gc->next_payment, 'medium') : '-',
        !is_null($mandate->next_possible_charge_date) ? format_date(strtotime($mandate->next_possible_charge_date), 'uc_store') : '-',
        $mandate->id,
        format_date(strtotime($mandate->created_at), 'uc_store'),
        $mandate->status,
      ];

      $payments_result = $partner->api([
        'endpoint' => 'payments',
        'action' => 'list',
        'mandate' => $gc->gcid,
        'limit' => 500,
      ]);
      $payments = $payments_result->response->body->payments;
    }
    else {
      // Subscriptions.
      $type = 'Subscription';
      $header = ['product', 'payment type', 'cost', 'price', 'payment interval', 'next possible charge date', 'gocardless id', 'created', 'status'];

      $payment_header = ['Payment name', 'Payment ID', 'Amount', 'Created at', 'Status', 'Charge customer at', ''];

      $rows[$product->id()] = [
        $product_select[$ucpid],
        $type,
        uc_currency_format($product->cost->value),
        uc_currency_format($product->price->value),
        $interval,
        format_date(strtotime($mandate->next_possible_charge_date), 'uc_store'),
        $mandate->id,
        format_date(strtotime($mandate->created_at), 'uc_store'),
        $mandate->status,
      ];

      $subs_result = $partner->api([
        'endpoint' => 'subscriptions',
        'action' => 'list',
        'mandate' => $gc->gcid,
      ]);
      $subscriptions = $subs_result->response->body->subscriptions;

      foreach ($subscriptions as $subscription) {
        if (isset($subscription->metadata->ucpid)) {
          if ($subscription->metadata->ucpid == $ucpid) {
            $subscription_id = $subscription->id;
            break;
          }
        }
      }

      $payments_result = $partner->api([
        'endpoint' => 'payments',
        'action' => 'list',
        'subscription' => $subscription_id,
        'limit' => 500,
      ]);
      $payments = $payments_result->response->body->payments;

      // One-off payments that have been created under the same mandate.
      $payments_of_result = $partner->api([
        'endpoint' => 'payments',
        'action' => 'list',
        'mandate' => $gc->gcid,
        'limit' => 500,
      ]);
      $payments_of = $payments_of_result->response->body->payments;
      if (count($payments_of) >= 1) {
        $payments = array_merge($payments, $payments_of);
        usort($payments, "\Drupal\uc_gc_client\Form\PaymentsForm::sortFunction");
      }
    }

    $payment_rows = [];
    $payment_total = 0;
    $payment_statuses = ['confirmed', 'paid_out'];
    foreach ($payments as &$payment) {

      if (!isset($payment->metadata->ucpid) || $payment->metadata->ucpid != $ucpid) {
        continue;
      }

      if ($payment->status == 'pending_submission') {
        $url = Url::fromRoute('uc_gc_client.cancel_payment', [
          'ucid' => $uc_order->id(),
          'payment_id' => $payment->id,
        ]);
        $link = \Drupal::l(t('Cancel'), $url);
      }
      else {
        $link = NULL;
      }

      $payment_rows[] = [
        $payment->description,
        $payment->id,
        uc_currency_format($payment->amount / 100, $sign),
        format_date(strtotime($payment->created_at), 'uc_store'),
        $payment->status,
        format_date(strtotime($payment->charge_date), 'uc_store'),
        $link,
      ];
      if (in_array($payment->status, $payment_statuses)) {
        $payment_total = $payment_total + $payment->amount;
      }
    }

    $form['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => isset($rows) ? $rows : NULL,
      '#empty' => t('There are no GoCardless payments for this order'),
    ];

    if (isset($payment_rows)) {

      $title = t('Payments for @title', ['@title' => $product_select[$ucpid]]);
      $form['payment_tables'] = [
        '#type' => 'details',
        '#title' => $title,
        '#open' => TRUE,
      ];
      $form['payment_tables']['payments_table'] = [
        '#theme' => 'table',
        '#header' => $payment_header,
        '#rows' => isset($payment_rows) ? $payment_rows : NULL,
        '#empty' => t('There are no payments for this product yet.'),
        '#suffix' => t('Total confirmed payments: @payment_total', ['@payment_total' => uc_currency_format($payment_total / 100, $sign)]),
      ];
    }

    $form['order_id'] = [
      '#type' => 'value',
      '#value' => $uc_order->id(),
    ];
    $form['mandate'] = [
      '#type' => 'value',
      '#value' => $mandate,
    ];
    $form['product_select'] = [
      '#type' => 'value',
      '#value' => $product_select,
    ];

    // Create payment section.
    $form['create_payment'] = [
      '#type' => 'details',
      '#title' => t('Create a payment'),
    ];
    $form['create_payment']['payment_amount'] = [
      '#type' => 'uc_price',
      '#title' => t('Amount'),
    ];
    $form['create_payment']['payment_title'] = [
      '#type' => 'textfield',
      '#title' => t('Payment title'),
    ];
    $form['create_payment']['charge_date'] = [
      '#title' => t('Charge customer at'),
      '#type' => 'date',
      '#date_format' => 'd M Y',
      '#default_value' => date('Y-m-d', strtotime($mandate->next_possible_charge_date)),
      '#date_year_range' => '0:+1',
      '#datepicker_options' => ['minDate' => 0],
    ];
    $form['create_payment']['payment_submit'] = [
      '#type' => 'submit',
      '#value' => 'Instruct GoCardless to create a Payment',
      '#validate' => ['::createPaymentValidate'],
      '#submit' => ['::createPaymentSubmit'],
    ];

    // Add or change Next Payment section.
    $form['next_payment'] = [
      '#type' => 'details',
      '#title' => t('Scheduled payment creation'),
      '#description' => t('<p>Add or change the date that the next scheduled payment will be created. (This is not the same date that the customer will be charged on.)</p>'),
      '#access' => $gc->type == 'P' ? TRUE : FALSE,
    ];
    $form['next_payment']['next_payment'] = [
      '#type' => 'datetime',
      '#default_value' => isset($gc->next_payment) ? DrupalDateTime::createFromTimestamp($gc->next_payment) : NULL,
      '#suffix' => '<br />',
    ];
    $form['next_payment']['next_payment_button'] = [
      '#type' => 'submit',
      '#value' => 'Change date',
      '#submit' => ['::nextPaymentSubmit'],
    ];

    // Scheduled Adjustments section.
    $query = db_select('uc_gc_client_schedules', 's');
    $adjustments = $query
      ->fields('s', ['sid', 'date', 'status','data'])
      ->condition('s.ucpid', $gc->ucpid, '=')
      ->condition('type', 'adjustment', '=')
      ->orderBy('timestamp', 'ASC')
      ->execute()->fetchAll();

    if (!empty($adjustments)) {
      $adj_rows = [];
      foreach ($adjustments as $adj) {

        $data = unserialize($adj->data);

        $url_arr = [
          'action' => 'cancel',
          'ucid' => $gc->ucid,
          'sid' => $adj->sid,
        ];

        $actions = '';
        if ($adj->status == 1) {
          $url_cancel = Url::fromRoute('uc_gc_client.adjust_action', $url_arr);
          $cancel = \Drupal::l(t('Cancel'), $url_cancel);
          $spacer = ' | ';
        }
        else {
          $cancel = NULL;
          $spacer = NULL;
        }

        $url_arr['action'] = 'delete';
        $url_delete = Url::fromRoute('uc_gc_client.adjust_action', $url_arr);
        $delete = \Drupal::l(t('Delete'), $url_delete);

        $actions = $cancel . $spacer . $delete;

        switch ($adj->status) {
          case 0:
            $status = 'Cancelled';
            break;

          case 1:
            $status = 'Pending';
            break;

          case 2:
            $status = 'Complete';
            break;
        }

        $adj_rows[] = [
          $data['title'],
          uc_currency_format($data['amount'], $sign),
          $adj->date,
          $status,
          new FormattableMarkup($actions, []),
        ];
      }
    }

    $form['adjust'] = [
      '#type' => 'details',
      '#title' => t('Scheduled adjustments'),
      '#description' => t("<p>Make a temporary change to the payment amount that is created with GoCardless.</p>"),
      '#prefix' => t('<div id="adjustments"></div>'),
      '#access' => $gc->type == 'P' ? TRUE : FALSE,
      '#open' => isset($adj_rows) ? TRUE : FALSE,
    ];

    if (isset($adj_rows)) {

      $form['adjust']['adjust_table'] = [
        '#type' => 'details',
        '#title' => t('Adjustments'),
        '#open' => TRUE,
      ];
      $adj_headers = ['Title', 'Adjustment', 'Date', 'Status', 'Actions'];

      // Adjustments tables.
      $form['adjust']['adjust_table']['table'] = [
        '#theme' => 'table',
        '#header' => $adj_headers,
        '#rows' => isset($adj_rows) ? $adj_rows : NULL,
        '#empty' => t('There are no scheduled adjustments for this product yet.'),
      ];
    }
    $form['adjust']['adjust_title'] = [
      '#type' => 'textfield',
      '#size' => 24,
      '#title' => t('Adjustment title'),
    ];
    $form['adjust']['adjustment'] = [
      '#type' => 'number',
      '#title' => t('Adjustment amount'),
      '#field_suffix' => t('£'),
      '#size' => 6,
      '#step' => .01,
    ];
    $form['adjust']['payments'] = [
      '#type' => 'number',
      '#size' => 6,
      '#title' => t('Number of payments'),
      '#default_value' => 1,
      '#step' => 1,
      '#min' => 1,
      '#max' => 52,
    ];
    $form['adjust']['starting_radio'] = [
      '#type' => 'radios',
      '#title' => t('Starting from'),
      '#options' => [0 => 'Next scheduled payment creation', 1 => 'Select another date'],
      '#default_value' => 0,
    ];
    $form['adjust']['starting'] = [
      '#type' => 'date',
      '#title' => t('Starting from'),
      '#description' => t('The adjustment(s) will begin on the first scheduled payment creation date after that specified here.'),
      '#default_value' => date('Y-m-d', $gc->next_payment),
      '#states' => [
        'visible' => [
          ':input[name="starting_radio"]' => ['value' => 1],
        ],
      ],
    ];
    $form['adjust']['plus'] = [
      '#type' => 'details',
      '#title' => t('and then'),
      '#description' => t("Additional scheduled adjustment to follow initial adjustment"),
    ];
    $form['adjust']['plus']['plus_adjustment'] = [
      '#type' => 'number',
      '#title' => t('Adjustment amount'),
      '#field_fix' => t('£'),
      '#size' => 6,
      '#step' => 1,
    ];
    $form['adjust']['plus']['plus_payments'] = [
      '#type' => 'number',
      '#size' => 6,
      '#title' => t('Number of payments'),
      '#default_value' => 0,
      '#step' => 1,
      '#min' => 0,
      '#max' => 52,
    ];
    $form['adjust']['adjust_button'] = [
      '#type' => 'submit',
      '#value' => 'Schedule',
      '#validate' => ['::adjustValidate'],
      '#submit' => ['::adjustSubmit'],
    ];
    return $form;
  }

  /**
   * Validate 'Scheduled adjustment' submissions.
   */
  public function adjustValidate(array $form, FormStateInterface $form_state) {

    $adjustment = $form_state->getValue(['adjustment']);
    if ($adjustment == 0) {
      $form_state->setErrorByName('adjustment', t('Adjustment cannot be zero'));
    }
  }

  /**
   * Process 'Scheduled adjustment' submissions.
   */
  public function adjustSubmit(array $form, FormStateInterface $form_state) {

    $ucpid = $form_state->getValue('ucpid');
    $order_id = $form_state->getValue('order_id');
    $order = Order::load($order_id);
    $product = $order->products[$ucpid];

    $sub = db_select('uc_gc_client', 'u')
      ->fields('u')
      ->condition('ucpid', $ucpid)
      ->execute()->fetch();

    // Create array containing scheduled adjs data.
    $int_params = $product->data->getValue()[0]['interval_params'];
    $starting = $sub->next_payment;
    if ($form_state->getValue('starting_radio') != 0) {
      $select_date = strtotime($form_state->getvalue('starting'));
      while ($starting < $select_date) {
        $string = '+' . $int_params['string'];
        $starting = strtotime($string, $starting);
      }
    }
    $payments = $form_state->getValue(['payments']);
    $amount = $form_state->getValue('adjustment');

    $inserts = [];
    for ($i = 0; $i < $payments; $i++) {
      $string = '+' . ($i * $int_params['length']) . ' ' . $int_params['unit'];
      $inserts[] = [
        'timestamp' => strtotime($string, $starting),
        'amount' => $amount,
      ];
      $ending = strtotime($string, $starting);
    }

    if ($form_state->getValue('plus_adjustment') != 0) {
      $plus_amount = $form_state->getValue('plus_adjustment');
      $plus_starting = strtotime('+' . $int_params['length'] . ' ' . $int_params['unit'], $ending);
      $plus_payments = $form_state->getValue('plus_payments');
      for ($i = 0; $i < $plus_payments; $i++) {
        $string = '+' . ($i * $int_params['length']) . ' ' . $int_params['unit'];
        $inserts[] = [
          'timestamp' => strtotime($string, $plus_starting),
          'amount' => $plus_amount,
        ];
      }
    }

    // Check validity of new scheduled adjustments.
    foreach ($inserts as $insert) {

      $date = date('D d M Y', $insert['timestamp']);

      // Calculate sum of scheduled adjs for date.
      $scheds = db_select('uc_gc_client_schedules', 's')
        ->fields('s')
        ->condition('type', 'adjustment')      
        ->condition('status', 1)      
        ->condition('date', $date)      
        ->condition('ucpid', $ucpid)
        ->execute()->fetchAll();
          
      $sum = 0;
      foreach($scheds as $sched) {
        $sched_data = unserialize($sched->data);
        $sum = $sum + $sched_data['amount'];
      }      

      $price = $product->price->value;
      $sum = $sum + $amount + $price;

      if ($sum < 1 && $sum != 0) {
        drupal_set_message(t('The schedule cannot be placed because the sum of scheduled adjustments, plus the price of the product, for @date is not zero, and is less than @amount, which is not allowed by GoCardless', ['@date' => $date, '@amount' => uc_currency_format(1)]), 'warning');
        return;
      }
    }

    // Add schedules data to database.
    foreach ($inserts as $insert) {

      $insert_date = date('D d M Y', $insert['timestamp']);
      $data = serialize([
        'title' => !empty($form_state->getValue('adjust_title')) ? $form_state->getValue('adjust_title') : 'Adjustment',
        'amount' => $insert['amount'],
      ]);
      $insert_result = db_insert('uc_gc_client_schedules')
        ->fields([
          'ucid' => $order_id,
          'ucpid' => $ucpid,
          'type' => 'adjustment',
          'date' => $insert_date,
          'timestamp' => $insert['timestamp'],
          'status' => 1,
          'data' => $data,
          'created' => REQUEST_TIME,
        ])
        ->execute();
    }
    drupal_set_message(t('New Scheduled Adjustment successfully added'));
  }

  /**
   * Process 'Add or change next payment' submission.
   */
  public function nextPaymentSubmit(array $form, FormStateInterface $form_state) {

    $next_payment = strtotime($form_state->getValue(['next_payment']));
    $ucpid = $form_state->getValue(['ucpid']);
    $title = $form_state->getValue(['product_select'])[$ucpid];

    $db_update = db_update('uc_gc_client')
      ->fields([
        'next_payment' => $next_payment,
      ])
      ->condition('ucpid', $ucpid, '=')
      ->execute();

    drupal_set_message(t('The next payment creation date for @title has been updated to @next_payment', ['@title' => $title, '@next_payment' => format_date($next_payment, 'medium')]));

  }

  /**
   * Validates Payment creation submission.
   */
  public function createPaymentValidate(array $form, FormStateInterface $form_state) {

    $mandate = $form_state->getValue(['mandate']);
    $amount = $form_state->getValue(['payment_amount']);

    if ($amount == 0) {
      $form_state->setErrorByName('payment_amount', t('Please provide an amount.'));
    }
    elseif ($amount < 1) {
      $form_state->setErrorByName('payment_amount', t('The minimum payment amount for GoCardless is @amount', ['@amount' => uc_currency_format(1)]));
    }

    // Check that specified date is greater than or equal to the
    // next possible charge date.
    $charge_date = $form_state->getValue(['charge_date']);
    if (!is_null($charge_date)) {
      if (strtotime($charge_date) < strtotime($mandate->next_possible_charge_date)) {
        $form_state->setErrorByName('charge_date', t('The date cannot be before the Next Possible Charge Date.', ['@amount' => uc_currency_format(1)]));
      }
    }
  }

  /**
   * Process Payment creation submission.
   */
  public function createPaymentSubmit(array $form, FormStateInterface $form_state) {

    $mandate = $form_state->getValue(['mandate']);
    $amount = $form_state->getValue(['payment_amount']);
    $ucpid = $form_state->getValue(['ucpid']);
    $order_id = $form_state->getValue(['order_id']);
    $order = Order::load($order_id);
    $currency_code = \Drupal::config('uc_store.settings')->get('currency.code');

    if (!empty($form_state->getValue(['payment_title']))) {
      $title = $form_state->getValue(['payment_title']);
    }
    else {
      $title = $order->products[$ucpid]->title->value;
    }

    $partner = new GoCardlessPartner();
    $result = $partner->api([
      'endpoint' => 'payments',
      'action' => 'create',
      'mandate' => $mandate->id,
      'amount' => $amount,
      'currency' => $currency_code,
      'description' => $title,
      'charge_date' => $form_state->getValue(['charge_date']),
      'metadata' => [
        'ucpid' => $ucpid,
      ],
    ]);

    if ($result->response->status_code != 201) {
      $message = t('Payment creation for @amount failed with GoCardless mandate @mandate.', ['@amount' => uc_currency_format($amount), '@mandate' => $mandate->id]);
      drupal_set_message($message, 'warning');
    }

    $message = t('A one-off payment for @amount has been created by Admin with GoCardless mandate @mandate.', ['@amount' => uc_currency_format($amount), '@mandate' => $mandate->id]);
    drupal_set_message($message);
    uc_order_comment_save($order->id(), $order->getOwnerId(), $message, 'order');
    $log = t('Order #@order_id:', ['@order_id' => $order->id()]) . ' ' . $message;
    \Drupal::logger('uc_gc_client')->notice($log, []);
  }

  /**
   * {@inheritdoc}
   */
  public static function switchProduct(array &$form, FormStateInterface $form_state) {
    $order_id = $form_state->getValue(['order_id']);
    $_SESSION['change_ucpid_' . $order_id]['ucpid'] = $form_state->getValue(['ucpid']);
    return $form;
  }

  /**
   * Sorts payments by created_at date id one-offs have been merged with
   * subsription payments.
   */
  public function sortFunction($a, $b) {
    return strtotime($a->created_at) - strtotime($b->created_at);
  }

  /**
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}

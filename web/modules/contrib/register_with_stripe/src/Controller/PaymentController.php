<?php 
/**
 * @file
 * Contains \Drupal\register_user_with_stripe_payment\Controller\PaymentController.
 */

namespace Drupal\register_user_with_stripe_payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Stripe;
use Stripe_Util;
use Stripe_Card;
use Stripe_Charge;
use Stripe_Customer;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Payment controller for the register_user_with_stripe_payment module.
 */
class PaymentController extends ControllerBase {

  public function register_user_with_stripe_payment_paid_users_with_stripe() {
    $header = array(
      'tid' => array(
        'data' => $this->t('TID'),
        'field' => 'tid',
        'sort' => 'asc',
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'stripeemail' => array(
        'data' => $this->t('Email'),
        'field' => 'stripeemail',
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'amount' => array(
        'data' => $this->t('Amount (Dollars)'),
        'field' => 'amount',
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'created' => array(
        'data' => $this->t('Created'),
        'field' => 'created',
        'sort' => 'desc',
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
    );
    $rows = [];
    $query = db_select('transactions', 't')->fields('t');
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(50);
    $results = $pager->execute();
    foreach ($results as $row) {
      $rows[] = [
        $row->tid,
        \Drupal\Component\Utility\SafeMarkup::checkPlain($row->stripeemail),
        $row->amount / 100,
        date('d-m-Y : H:i:s', $row->created),
      ];
    }

    $build['pager_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('There are no date formats found in the db'),
      '#responsive' => TRUE,
    );
    // attach the pager theme
    $build['pager_pager'] = array('#theme' => 'pager');
    return $build;

  }

  public function register_user_with_stripe_payment_start($uid) {
    $st_lib_path = function_exists('libraries_get_path') ? libraries_get_path('stripe') : 'sites/all/libraries/stripe';
    $st_platform = $st_lib_path . '/Stripe.php';
    include $st_platform;
     
    $cuser = \Drupal::entityManager()->getStorage('user')->load($uid);
    $uid = $cuser->id();
    $mail = $cuser->getEmail();

    $stripe = [
      "secret_key" => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_api_secret_key'),
      "publishable_key" => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_api_publishable_key'),
    ];
    
    Stripe::setApiKey($stripe['secret_key']);

    $amount = \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_registration_amount') * 100;
    
    $charge_form = \Drupal::formBuilder()->getForm('\Drupal\register_user_with_stripe_payment\Form\StripeCharge', $uid, $mail, $stripe, $amount);
    
    return $charge_form;
  }

  public function register_user_with_stripe_payment_stripe_complete($uid) {
    $user = \Drupal::currentUser();
    $st_lib_path = function_exists('libraries_get_path') ? libraries_get_path('stripe') : 'sites/all/libraries/stripe';
    $st_platform = $st_lib_path . '/Stripe.php';
    @include $st_platform;

    $stripe = [
      "secret_key" => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_api_secret_key'),
      "publishable_key" => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_api_publishable_key'),
    ];
    Stripe::setApiKey($stripe['secret_key']);
    $token = $_POST['stripeToken'];
    if (count($_POST) > 0) {
      $customer = Stripe_Customer::create([
        'email' => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_customer_email'),
        'card' => $token,
      ]);
      $amount = \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_registration_amount') * 100;
      Stripe_Charge::create([
        'customer' => $customer->id,
        'amount' => $amount,
        'currency' => 'usd',
      ]);
      db_insert('transactions')
        ->fields([
        'stripetoken' => $_POST['stripeToken'],
        'stripeemail' => $_POST['stripeEmail'],
        'uid' => $uid,
        'amount' => $amount,
        'created' => time(),
      ])
        ->execute();
      drupal_set_message(t('Successfully charged &#36;!amount', [
        '!amount' => ($amount / 100)
        ]));
      $user = \Drupal::entityManager()->getStorage('user')->load($uid);
      $user->status = 1;
      $user->save();

      user_login_finalize($user);
    }
    else {
      \Drupal::logger('stripe_payment_failure')->notice('bad response from stripe %err', [
        '%err' => 'payment failure due to improper response from stripe'
        ]);
      $err_msg = t('Your payment is not done successfully. Please contact the administrator');
      drupal_set_message($err_msg, 'error');
    }
    return new RedirectResponse(\Drupal::url('<front>'));
  }

}

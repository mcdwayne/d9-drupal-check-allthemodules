<?php

/**
 * @file
 * Admin page callback file for the register user with stripe payment module.
 */
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form constructor to the set the api add form.
 *
 * @ingroup forms
 */
function register_user_with_stripe_payment_config_form($form, &$form_state) {
  $form['register_user_with_stripe_payment_api_secret_key'] = array(
    '#type' => 'textfield',
    '#title' => 'Secret Key',
    '#default_value' => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_api_secret_key'),
    '#description' => '<p>' . t('Enter your secret key e.g sk_test_BQokikJOvBiI2HlWgH4olfQ2')
    . '</p>',
    '#required' => TRUE,
  );
  $form['register_user_with_stripe_payment_api_publishable_key'] = array(
    '#type' => 'textfield',
    '#title' => 'Publishable Key',
    '#default_value' => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_api_publishable_key'),
    '#description' => '<p>' . t('Enter your api key e.g pk_test_6pRNASCoBOKtIshFeQd4XMUh')
    . '</p>',
    '#required' => TRUE,
  );
  $form['register_user_with_stripe_payment_customer_email'] = array(
    '#type' => 'textfield',
    '#title' => 'Customer Email',
    '#default_value' => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_customer_email'),
    '#description' => '<p>' . t('Enter your customer email id')
    . '</p>',
    '#required' => TRUE,
  );
  $form['register_user_with_stripe_payment_registration_amount'] = array(
    '#type' => 'textfield',
    '#title' => 'Registration Amount',
    '#default_value' => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_registration_amount'),
    '#description' => '<p>' . t('Enter registration amount e.g 10 or 10.50')
    . '</p>',
    '#required' => TRUE,
  );
  return system_settings_form($form);
}

/**
 * Function to initiate the payment flow.
 *
 * @param int $uid
 *   Pass the stripe keys.
 */
function register_user_with_stripe_payment_start($uid) {
  $st_lib_path = function_exists('libraries_get_path') ? libraries_get_path('stripe') : 'sites/all/libraries/stripe';
  $st_platform = $st_lib_path . '/Stripe.php';
  @include $st_platform;
  $cuser = \Drupal::entityManager()->getStorage('user')->load($uid);

  $stripe = array(
    "secret_key" => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_api_secret_key'),
    "publishable_key" => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_api_publishable_key'),
  );
  Stripe::setApiKey($stripe['secret_key']);
  $amount = \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_registration_amount') * 100;
  \Drupal::formBuilder()->getForm('register_user_with_stripe_payment_charge_form', $cuser, $stripe, $amount);
}

/**
 * Function to complete the payment flow.
 *
 * @param int $uid
 *   Pass the stripe variables and redirect user the main page.
 */
function register_user_with_stripe_payment_stripe_complete($uid) {
  $user = \Drupal::currentUser();
  $st_lib_path = function_exists('libraries_get_path') ? libraries_get_path('stripe') : 'sites/all/libraries/stripe';
  $st_platform = $st_lib_path . '/Stripe.php';
  @include $st_platform;

  $stripe = array(
    "secret_key" => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_api_secret_key'),
    "publishable_key" => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_api_publishable_key'),
  );
  Stripe::setApiKey($stripe['secret_key']);
  $token = $_POST['stripeToken'];
  if (count($_POST) > 0) {

    $customer = Stripe_Customer::create(array(
        'email' => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_customer_email'),
        'card' => $token,
    ));

    $amount = \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_registration_amount') * 100;
    Stripe_Charge::create(array(
      'customer' => $customer->id,
      'amount' => $amount,
      'currency' => 'usd',
    ));
    db_insert('transactions')
      ->fields(array(
        'stripetoken' => $_POST['stripeToken'],
        'stripeemail' => $_POST['stripeEmail'],
        'uid' => $uid,
        'amount' => $amount,
        'created' => time(),
      ))
      ->execute();
    drupal_set_message(t('Successfully charged &#36;!amount', array('!amount' => ($amount / 100))));
    $user = \Drupal::entityManager()->getStorage('user')->load($uid);
    $user->status = 1;
    $user->save();
    user_login_finalize($user);
  }
  else {
    \Drupal::logger('stripe_payment_failure')->notice('bad response from stripe %err', array('%err' => 'payment failure due to improper response from stripe'));
    $err_msg = t('Your payment is not done successfully. Please contact the administrator');
    drupal_set_message($err_msg, 'error');
  }
  return new RedirectResponse(\Drupal::url('<front>'));
}
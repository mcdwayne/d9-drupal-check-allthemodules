<?php

namespace Drupal\uc_gc_client\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;
use Drupal\uc_gc_client\Controller\GoCardlessPartner;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Defines the GoCardless Client payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "gc_client",
 *   name = @Translation("GoCardless Client"),
 * )
 */
class GoCardlessClient extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_gc_client_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    $default_config = \Drupal::config('uc_gc_client.settings');
    $config = [];
    foreach ([
      'sandbox',
      'payment_limit',
      'create_payment',
      'payments_tab',
      'currencies',
      'fixer',
      'checkout_review',
      'checkout_label',
      'log_webhook',
      'log_api',
      'warnings_email',
      'dom'] 
    as $element) {
      $config[$element] = $default_config->get($element);
    }
    return $config;
  }


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['#cache'] = ['max-age' => 0];
    $config = $this->configuration;
    $settings = \Drupal::config('uc_gc_client.settings')->get();
    $sandbox = $settings['sandbox'];
    $sandbox ? $ext = '_sandbox' : $ext = '_live';

    $connected = FALSE;
    if (!is_null($settings['partner_user' . $ext]) && !is_null($settings['partner_pass' . $ext])) {
      $partner = new GoCardlessPartner();
      $response = $partner->get();
      $host = \Drupal::request()->getHost();
      if (isset($response->client_domain)) {
        if ($host == $response->client_domain) $connected = TRUE;
      }
    }

    $form['connect'] = [
      '#type' => 'details',
      '#title' => t('Connect with GoCardless'),
      '#open' => TRUE,
      '#tree' => FALSE,
    ];

    $form['connect']['sandbox'] = [
      '#type' => 'checkbox',
      '#title' => t('<b>Enable Sandbox</b>'),
      '#description' => t('Sandbox: GoCardless will operate in a test environment, and no real banking activity will occur.'),
      '#default_value' => $sandbox,
      '#ajax' => [
        'callback' => 'Drupal\uc_gc_client\Plugin\Ubercart\PaymentMethod\GoCardlessClient::sandboxCallback',
      ],
    ];

    $markup = '<br /><p><b>Connect / disconnect with GoCardless</b></p>';
    if (!$connected) {
      $markup .= "<p>After clicking 'Connect' you will be redirected to the GoCardless where you can create an account and connect your site as a client of Seamless-CMS.co.uk</p>";

      $form['connect']['markup'] = [
        '#markup' => $markup,
      ];

      $form['connect']['submit'] = [
        '#type' => 'submit',
        '#disabled' => !$sandbox && !isset($_SERVER['HTTPS']) ? TRUE : FALSE,
        '#value' => $sandbox ? 'Connect SANDBOX' : 'Connect LIVE',
        '#submit' => ['Drupal\uc_gc_client\Plugin\Ubercart\PaymentMethod\GoCardlessClient::submitConnect'],
        '#validate' => ['Drupal\uc_gc_client\Plugin\Ubercart\PaymentMethod\GoCardlessClient::submitValidate'],
        '#suffix' => !$sandbox && !isset($_SERVER['HTTPS']) ? t('<br /><i>Site needs to be secure (https) before you can connect to GoCardless LIVE.</i>') : NULL,
      ];
    }
    else {
      $form['connect']['markup'] = [
        '#markup' => $markup,
      ];
      $form['connect']['ext'] = [
        '#type' => 'value',
        '#value' => $ext,
      ];
      $form['connect']['disconnect'] = [
        '#type' => 'submit',
        '#value' => $sandbox ? 'Disconnect SANDBOX' : 'Disconnect LIVE',
        '#submit' => ['Drupal\uc_gc_client\Plugin\Ubercart\PaymentMethod\GoCardlessClient::submitDisconnect'],
        '#attributes' => [
          'onclick' => 'if (!confirm("Are you sure you want to disconnect your site from GoCardless?")) {return FALSE;}',
        ],
      ];
    }

    global $base_url;
    $webhook_url = $base_url . '/gc_client/webhook';
    if ($sandbox) {
      $gc_webhook_url = 'https://manage-sandbox.gocardless.com/developers/webhook-endpoints/create';
    }
    else {
      $gc_webhook_url = 'https://manage.gocardless.com/developers/webhook-endpoints/create';
    }
    $webhook_prefix = t('To receive webhooks, add <i>@webhook_url</i> as webhook URL and set the secret to the same as the Webhook Secret field from <a target="new" href="@gc_webhook_url">here</a>.<br /><br />', ['@webhook_url' => $webhook_url, '@gc_webhook_url' => $gc_webhook_url]);

    $form['connect']['partner_webhook' . $ext] = [
      '#type' => 'textfield',
      '#title' => 'Webhook secret',
      '#default_value' => isset($config['partner_webhook' . $ext]) ? $config['partner_webhook' . $ext] : NULL,
      '#field_prefix' => $webhook_prefix,
    ];

    // Global.
    $form['global'] = [
      '#type' => 'details',
      '#title' => t('Global settings'),
      '#open' => TRUE,
      '#tree' => FALSE,
    ];
/*
    $form['global']['dom'] = [
      '#type' => 'textfield',
      '#title' => t('Day(s) of month that first payments go out on'),
      '#default_value' => $config['dom'],
      '#size' => 25,
      '#maxlength' => 24,
      '#description' => t("Enter one or more days of the month, upon which direct debits will start. The system will automatically choose the next available date from those you have provided. Values must be seperated by a comma.<br />These values will be ignored if there is a valid Start Date set for individual products, or if they are set to create a payment immediately."),
      '#required' => FALSE,
    ];
*/
    $form['global']['payments_tab'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide Payments tab'),
      '#default_value' => $config['payments_tab'],
      '#description' => t("Ubercart Payments do not work with GoCardless, so checking this will hide the Payments tab when viewing any orders created through GoCardless. (Cache needs to be cleared to make this work.)"),
    ];
    $form['global']['currencies'] = [
      '#type' => 'checkbox',
      '#title' => t('Create payments in foreign currencies'),
      '#default_value' => $config['currencies'],
      '#description' => t("Use foreign currency, and adjust payment amount for international customers, according to current exchange rates at <a target='new' href='http://fixer.io'>fixer.io</a>. This only applies when the currency of the customer's country is different to the store's default currency. SEPA and /or AutoGiro regions need to be enabled on your GoCardless account for this to work."),
    ];
    $form['global']['fixer'] = [
      '#type' => 'textfield',
      '#title' => t('fixer.io'),
      '#default_value' => $config['fixer'],
      '#description' => "API key for fixer.io",
      '#size' => 40,
      '#maxlength' => 40,
      '#states' => [
        'visible' => [
          'input[name="currencies"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['global']['payment_limit'] = [
      '#type' => 'number',
      '#title' => t('Maximum payments'),
      '#default_value' => $config['payment_limit'],
      '#size' => 3,
      '#min' => 1,
      '#description' => t("The maximum number of One-off payments that can be raised automatically, per order, per day. (Does not apply to Subscription payments).<br />If the amount is exceeded, a warning email is sent to the specified address above. Leave unset for unlimitted."),
      '#required' => FALSE,
    ];

    $form['global']['warnings_email'] = [
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#default_value' => $config['warnings_email'],
      '#description' => "Email address to send warnings.",
      '#size' => 40,
      '#maxlength' => 40,
    ];

    // Checkout options.
    $form['checkout'] = [
      '#type' => 'details',
      '#title' => t('Checkout settings'),
      '#open' => TRUE,
      '#tree' => FALSE,
    ];
    $form['checkout']['checkout_review'] = [
      '#title' => t('<b>Disable Checkout Review page</b>'),
      '#type' => 'checkbox',
      '#default_value' => $config['checkout_review'],
      '#required' => FALSE,
      '#description' => t("Check this to emit the Checkout Review page. <b>Do not</b> use this if you are using other payment methods in addition to GoCardless Client."),
    ];
    $form['checkout']['checkout_label'] = [
      '#type' => 'textfield',
      '#title' => t('Checkout button label'),
      '#description' => t('Customize the label of the final checkout button when the customer is about to pay.'),
      '#default_value' => $config['checkout_label'],
    ];

    // Logging options.
    $form['log'] = [
      '#title' => t('Logging'),
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => FALSE,
    ];
    $form['log']['log_webhook'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#type' => 'checkbox',
      '#title' => t('<b>Enable webhook logging</b>'),
      '#description' => 'Webhooks recieved from GoCardless will be written to the log.',
      '#default_value' => $config['log_webhook'],
    ];
    $form['log']['log_api'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#type' => 'checkbox',
      '#title' => t('<b>Enable API logging</b>'),
      '#description' => 'Responses from the Partner site to API posts will be written to the log.',
      '#default_value' => $config['log_api'],
    ];
    return $form;
  }

  /**
   * //TODO 
   * {@inheritdoc} 
   */
  public static function sandboxCallback(array &$form, FormStateInterface $form_state) {

    $sandbox = $form_state->getValue('sandbox');
    \Drupal::configFactory()->getEditable('uc_gc_client.settings')->set('sandbox', $sandbox)->save();

    $id = $form_state->getValue('id');
    if (empty($id)) $path = '/admin/store/config/payment/add/gc_client';
    else $path = '/admin/store/config/payment/method/' . $id;
    
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($path));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $element => $value) {
      if (!in_array($element, ['disconnect', 'type', 'label', 'id', 'ext', 'submit', 'op', 'form_id', 'form_token', 'form_build_id'])) {
        $form_state->setValue(['settings', $element], $value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

    $id = $form_state->getValue('id');
    \Drupal::state()->set('uc_gc_client_payment_method_id', 'uc_payment.method.' . $id);
  }

  public static function submitValidate(array &$form, FormStateInterface $form_state) {
/*
    dpm($form);
    dpm($form_state);
    global $base_url;
    $client_url = urlencode($base_url . '/gc_client/connect_complete');
    $partner_domain = \drupal::config('uc_gc_client.settings')->get('partner_url');
    $site_email = \drupal::config('system.site')->get('mail');
    $form_state->getvalue('settings')['sandbox'] ? $env = 'sandbox' : $env = 'live';
    $url = $partner_domain . '/gc_partner/connect/' . $env . '?mail=' . $site_email . '&client_url=' . $client_url;
    $response = new trustedredirectresponse($url);
    $form_state->setresponse($response);
*/
  }

  /**
   * Redirect to Partner site to activate the GoCardless OAuth Flow.
   */
  public static function submitConnect(array &$form, FormStateInterface $form_state) {

    global $base_url;
    $client_url = urlencode($base_url . '/gc_client/connect_complete');
    $partner_domain = \drupal::config('uc_gc_client.settings')->get('partner_url');
    $site_email = \drupal::config('system.site')->get('mail');
    $form_state->getvalue('sandbox') ? $env = 'sandbox' : $env = 'live';
    $url = $partner_domain . '/gc_partner/connect/' . $env . '?mail=' . $site_email . '&client_url=' . $client_url;
    $response = new trustedredirectresponse($url);
    $form_state->setresponse($response);

    //Remove the 'desination=' parameter from query string if it exists.
    $request = \Drupal::request();
    $request->query->set('destination', NULL);
  }

  /**
   * Disconnects client site from GC partner site.
   */
  public static function submitDisconnect(array &$form, FormStateInterface $form_state) {

    $partner = new GoCardlessPartner();
    $result = $partner->api([
      'endpoint' => 'oauth',
      'action' => 'revoke',
    ]);

    if ($result->response == 200) {
      drupal_set_message(t('You have disconnected successfully from GoCardless'));
    }
    else {
      drupal_set_message(t('There was a problem disconnecting from GoCardless'), 'error');
    }

    if (isset($_SESSION['gc_client_cookie_created'])) {
      unset($_SESSION['gc_client_cookie_created']);
    }

    $ext = $form_state->getValue('ext');
    $config = \Drupal::service('config.factory')->getEditable('uc_gc_client.settings');
    $config->set('org_id' . $ext, NULL)->save();
    $config->set('partner_user' . $ext, NULL)->save();
    $config->set('partner_pass' . $ext, NULL)->save();
  }
}

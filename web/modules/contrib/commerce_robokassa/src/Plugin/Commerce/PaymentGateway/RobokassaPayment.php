<?php

namespace Drupal\commerce_robokassa\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Robokassa payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "robokassa_payment",
 *   label = "Robokassa payment",
 *   display_label = "Robokassa",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_robokassa\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "mir", "jcb", "unionpay", "mastercard", "visa",
 *   },
 * )
 */
class RobokassaPayment extends OffsitePaymentGatewayBase implements RobokassaPaymentInterface {

  /**
   * The price rounder.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The http cleint.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, RounderInterface $rounder, LanguageManagerInterface $language_manager, Client $http_client, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->rounder = $rounder;
    $this->languageManager = $language_manager;
    $this->httpClient = $http_client;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('commerce_price.rounder'),
      $container->get('language_manager'),
      $container->get('http_client'),
      $container->get('logger.factory')->get('commerce_robokassa')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'MrchLogin' => '',
      'pass1' => '',
      'pass2' => '',
      'server_url_live' => 'https://auth.robokassa.ru/Merchant/Index.aspx',
      'server_url_test' => 'https://auth.robokassa.ru/Merchant/Index.aspx',
      'hash_type' => 'md5',
      'show_robokassa_fee_message' => TRUE,
      'allowed_currencies' => [],
      'logging' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['MrchLogin'] = [
      '#type' => 'textfield',
      '#title' => $this->t('login'),
      '#description' => t('Your robokassa login'),
      '#default_value' => $this->configuration['MrchLogin'],
      '#required' => TRUE,
    ];

    $form['pass1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First password'),
      '#description' => t('Password 1'),
      '#default_value' => $this->configuration['pass1'],
      '#required' => TRUE,
    ];

    $form['pass2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Second password'),
      '#description' => t('Password 2'),
      '#default_value' => $this->configuration['pass2'],
      '#required' => TRUE,
    ];

    $form['server_url_live'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server URL'),
      '#default_value' => $this->configuration['server_url_live'],
    ];

    $form['server_url_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server Test URL'),
      '#default_value' => $this->configuration['server_url_test'],
    ];

    $form['hash_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Hash type'),
      '#options' => [
        'md5' => 'md5',
        'ripemd160' => 'ripemd160',
        'sha1' => 'sha1',
        'sha256' => 'sha256',
        'sha384' => 'sha384',
        'sha512' => 'sha512',
      ],
      '#default_value' => $this->configuration['hash_type'],
      '#required' => TRUE,
    ];

    $form['allowed_currencies'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Currencies'),
      '#options' => $this->paymentMethodsList(),
      '#default_value' => $this->configuration['allowed_currencies'],
    ];

    $form['show_robokassa_fee_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show robokassa fee message'),
      '#default_value' => $this->configuration['show_robokassa_fee_message'],
    ];

    $form['logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Logging'),
      '#default_value' => $this->configuration['logging'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['MrchLogin'] = $values['MrchLogin'];
      if (!empty($values['pass1'])) {
        $this->configuration['pass1'] = $values['pass1'];
      }
      if (!empty($values['pass2'])) {
        $this->configuration['pass2'] = $values['pass2'];
      }
      $this->configuration['server_url_live'] = $values['server_url_live'];
      $this->configuration['server_url_test'] = $values['server_url_test'];
      $this->configuration['hash_type'] = $values['hash_type'];
      $this->configuration['show_robokassa_fee_message'] = $values['show_robokassa_fee_message'];
      $this->configuration['allowed_currencies'] = $values['allowed_currencies'];
      $this->configuration['logging'] = $values['logging'];
    }
  }

  function paymentMethodsList() {
    $url = 'https://auth.robokassa.ru/Merchant/WebService/Service.asmx/GetCurrencies';
    $data = [
      'MerchantLogin' => $this->configuration['MrchLogin'],
      'Language' => $this->languageManager->getCurrentLanguage()->getId() == 'ru' ? 'ru' : 'en',
    ];
    $response = $this->httpClient->get($url, ['query' => $data]);

    $xmlstring = $response->getBody()->getContents();
    $xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
    $json = json_encode($xml);
    $array = json_decode($json,TRUE);
    $ret = [];

    if (!isset($array['Groups'])) {
      return $ret;
    }

    foreach($array['Groups'] as $groups) {
      foreach($groups as $group) {
        foreach($group['Items'] as $item) {
          if (isset($item['@attributes'])) {
            $item = array($item);
          }
          foreach($item as $currency) {
            $ret[$currency['@attributes']['Label']] = $currency['@attributes']['Name'];
          }
        }
      }
    }

    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    drupal_set_message($this->t('Payment was processed'));
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    /** @var PaymentInterface $payment */
    $payment = $this->doValidatePost($request);

    if (!$payment) {
      return FALSE;
    }

    $payment->setState('completed');
    $payment->save();
  }

  protected function doCancel(PaymentInterface $payment, array $status_response) {
    $payment->setState('authorization_expired');
    $payment->save();

    return TRUE;
  }

  /**
   * Helper to validate robokassa $_POST data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $data
   *   $_POST to be validated.
   * @param bool $is_interaction
   *   Fallback call flag.
   *
   * @return bool|mixed
   *   Transaction according to POST data or due.
   */
  public function doValidatePost(Request $request, $is_interaction = TRUE) {
    $data = $request->request->all();

    // Exit now if the $_POST was empty.
    if (empty($data)) {
     $this->logger->warning('Interaction URL accessed with no POST data submitted.');

     return FALSE;
    }

    // Exit now if any required keys are not exists in $_POST.
    $required_keys = array('OutSum','InvId');
    if ($is_interaction) {
      $required_keys[] = 'SignatureValue';
    }
    $unavailable_required_keys = array_diff_key(array_flip($required_keys), $data);

    if (!empty($unavailable_required_keys)) {
      $this->logger->warning('Missing POST keys. POST data: <pre>!data</pre>', array('!data' => print_r($unavailable_required_keys, TRUE)));
      return FALSE;
    }

    // Exit now if missing Checkout ID.
    if (empty($this->configuration['MrchLogin'])) {
      $info = array(
        '!settings' => print_r($this->configuration, 1),
        '!data' => print_r($data, TRUE),
      );
      $this->logger->warning('Missing merchant ID.  POST data: <pre>!data</pre> <pre>!settings</pre>',
        $info);
      return FALSE;
    }

    if ($is_interaction) {
      if ($this->configuration) {
        // Robokassa Signature.
        $robo_sign = $data['SignatureValue'];

        // Create own Signature.
        $signature_data = array(
          $data['OutSum'],
          $data['InvId'],
          $this->configuration['pass2'],
        );
        if (isset($data['shp_trx_id'])) {
          $signature_data[] = 'shp_trx_id=' . $data['shp_trx_id'];
        }

        $sign = hash($this->configuration['hash_type'], implode(':', $signature_data));

        // Exit now if missing Signature.
        if (Unicode::strtoupper($robo_sign) != Unicode::strtoupper($sign)) {
          $this->logger->warning('Missing Signature. 1 POST data: !data', array('!data' => print_r($data, TRUE)));
          return FALSE;
        }
      }
    }

    try {
      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      $payment = $this->entityTypeManager->getStorage('commerce_payment')
        ->load($data['shp_trx_id']);
    }
    catch (InvalidPluginDefinitionException $e) {
      $this->logger->warning('Missing transaction id.  POST data: !data', array('!data' => print_r($data, TRUE)));
      return FALSE;
    }

    $amount = new Price($data['OutSum'], $payment->getAmount()->getCurrencyCode());

    if (!$payment->getAmount()->equals($amount)) {
      $this->logger->warning('Missing transaction id amount.  POST data: !data', array('!data' => print_r($data, TRUE)));
      return FALSE;
    }
    return $payment;
  }

  /**
   * Sets transaction 'status' and 'message' depending on RBS status.
   *
   * @param object $transaction
   * @param int $remote_status
   */
  public function setLocalState(PaymentInterface $payment, $remote_status) {
    switch ($remote_status) {
      case 'success':
        $payment->setState('completed');
        break;

      case 'fail':
        $payment->setState('authorization_voided');
        break;
    }
  }

}

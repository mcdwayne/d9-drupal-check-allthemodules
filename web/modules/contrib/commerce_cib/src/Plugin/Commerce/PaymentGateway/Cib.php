<?php

namespace Drupal\commerce_cib\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_cib\EncryptionInterface;
use Drupal\commerce_cib\Event\CibEvents;
use Drupal\commerce_cib\Event\FailedPayment;
use Drupal\commerce_cib\Event\NoCommunication;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannel;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the CIBt payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "cib",
 *   label = "CIB",
 *   display_label = "CIB",
 *   payment_type = "payment_cib",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_cib\PluginForm\CibForm",
 *   },
 * )
 */
class Cib extends OffsitePaymentGatewayBase implements CibInterface, SupportsRefundsInterface {

  // Default value for transaction timeouts in seconds.
  const TIMEOUT = 700;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The encryption service.
   *
   * @var \Drupal\commerce_cib\EncryptionInterface
   */
  protected $encryption;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, ClientInterface $client, EncryptionInterface $encryption, LoggerChannel $logger, ContainerAwareEventDispatcher $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->httpClient = $client;
    $this->encryption = $encryption;
    $path = $this->getMode() === 'live' ? $this->configuration['des-live'] : $this->configuration['des-test'];
    $this->encryption->setKeyfile($path);
    $this->logger = $logger;
    $this->eventDispatcher = $event_dispatcher;
    $this->time = $time;
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
      $container->get('http_client'),
      $container->get('commerce_cib.encryption'),
      $container->get('commerce_cib.logger'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'pid' => '',
      'cur' => 'HUF',
      'des-test' => '',
      'des-live' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['pid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CIB Shop ID'),
      '#description' => $this->t('The shop ID received from CIB in email. Has the format "ABC0001".'),
      '#default_value' => $this->configuration['pid'],
      '#required' => TRUE,
    ];
    $form['cur'] = [
      '#type' => 'radios',
      '#title' => $this->t('Currency'),
      '#description' => $this->t('The currency of the payment.'),
      '#options' => [
        'EUR' => 'EUR',
        'HUF' => 'HUF',
      ],
      '#disabled' => TRUE,
      '#default_value' => $this->configuration['cur'],
      '#required' => TRUE,
    ];
    $form['des-test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test keyfile path'),
      '#description' => $this->t('The absolute path of the des keyfile used for encryption on the dev site.'),
      '#default_value' => isset($this->configuration['des-test']) ? $this->configuration['des-test'] : '',
      '#required' => TRUE,
    ];
    $form['des-live'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Live keyfile path'),
      '#description' => $this->t('The absolute path of the des keyfile used for encryption on the live site.'),
      '#default_value' => isset($this->configuration['des-live']) ? $this->configuration['des-live'] : '',
      '#required' => TRUE,
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
      $this->configuration['pid'] = $values['pid'];
      $this->configuration['cur'] = $values['cur'];
      $this->configuration['des-test'] = $values['des-test'];
      $this->configuration['des-live'] = $values['des-live'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $query_pms = $request->query->all();
    // Getting answer from the CIB payment gateway.
    $crypto = 'DATA=' . $query_pms['DATA'] . '&PID=' . $query_pms['PID'] . '&CRYPTO=1';
    $cleartext = $this->encryption->decrypt($crypto);
    parse_str($cleartext, $msgt21);
    if (($msgt21['MSGT'] == '21')) {
      // Closing the transaction.
      $amo = (int) $order->getTotalPrice()->getNumber();
      $msgt32 = [
        'CRYPTO' => 1,
        'MSGT' => 32,
        'PID' => $this->configuration['pid'],
        'TRID' => $msgt21['TRID'],
        'AMO' => $amo,
      ];
      $response = $this->sendRequest($msgt32);
      try {
        $this->analyseMsgt32Or33($response, $order);
      }
      catch (PaymentGatewayException $e) {
        $msgt33 = [
          'CRYPTO' => 1,
          'MSGT' => 33,
          'PID' => $this->configuration['pid'],
          'TRID' => $msgt32['TRID'],
          'AMO' => $amo,
        ];
        $response = $this->sendRequest($msgt33);
        $this->analyseMsgt32Or33($response, $order);
      }
    }
    else {
      $event = new NoCommunication($order);
      $this->eventDispatcher->dispatch(CibEvents::NO_COMMUNICATION, $event);
    }
  }

  /**
   * Analyse a 32 or 33 response.
   *
   * @param array $response
   *   The response array.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  protected function analyseMsgt32Or33(array $response, OrderInterface $order) {
    if (empty($response['TRID'])) {
      throw new PaymentGatewayException('No TRID returned for MSGT' . $response['MSGT']);
    }
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entityTypeManager->getStorage('commerce_payment')->loadByRemoteId($response['TRID']);
    if (!empty($payment)) {
      if ((int) $payment->getAmount()->getNumber() != $response['AMO']) {
        throw new PaymentGatewayException('AMO mismatch in 31 response.');
      }
      $payment->payment_cib_msg = $response['MSGT'];
      $payment->payment_cib_rc = $response['RC'];
      $payment->payment_cib_rt = $response['RT'];
      $payment->payment_cib_anum = $response['ANUM'];
      $end = $payment->payment_cib_end = $this->time->getCurrentTime();
      $start = $payment->payment_cib_start->value;

      if ($response['RC'] == '00') {
        $payment->setState('completed')->save();
      }
      else {
        if ($end - $start > self::TIMEOUT) {
          $payment->setState('pending')->save();
        }
        else {
          $payment->setState('voided')->save();
        }
        $event = new FailedPayment($payment);
        $this->eventDispatcher->dispatch(CibEvents::FAILED_PAYMENT, $event);
        throw new PaymentGatewayException('Payment has failed.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createUrl($query, $market_customer = 'market') {
    $cleartext = '';
    foreach ($query as $key => $value) {
      $cleartext .= $key . '=' . rawurlencode($value) . '&';
    }
    $cleartext = substr($cleartext, 0, -1);

    if (!$this->encryption->getKey()) {
      $des = ($this->getMode() == 'test') ? $this->getConfiguration()['des-test'] : $this->getConfiguration()['des-live'];
      $this->encryption->setKeyfile($des);
    }
    $crypto = $this->encryption->encrypt($cleartext);

    if ($market_customer === 'market') {
      if ($this->getMode() === 'live') {
        $base_url = 'http://eki.cib.hu:8090/market.saki';
      }
      else {
        $base_url = 'http://ekit.cib.hu:8090/market.saki';
      }
    }
    else {
      if ($this->getMode() == 'live') {
        $base_url = 'https://eki.cib.hu/customer.saki';
      }
      else {
        $base_url = 'https://ekit.cib.hu/customer.saki';
      }
    }
    return $base_url . '?' . $crypto;
  }

  /**
   * {@inheritdoc}
   */
  public function simpleMsgt(PaymentInterface $payment, $msgt = 37) {
    $config = $payment->getPaymentGateway()->getPluginConfiguration();
    $query = [
      'MSGT' => $msgt,
      'PID' => $config['pid'],
      'TRID' => $payment->getRemoteId(),
      'AMO' => (int) $payment->getAmount()->getNumber(),
    ];
    return $this->sendRequest($query);
  }

  /**
   * {@inheritdoc}
   */
  public function sendRequest(array $query) {
    $this->logger->notice('Request to CIB: ' . json_encode($query));
    $url = $this->createUrl($query);
    $response = $this->httpClient->get($url);
    if ($response->getStatusCode() == 200) {
      $body = $response->getBody();
      if (strpos($body, 'DATA=') === FALSE) {
        throw new PaymentGatewayException('Something went wrong during payment processing. Try again.');
      }
      $cleartext = $this->encryption->decrypt($body);
      parse_str($cleartext, $response_array);
      $this->logger->notice('Response from CIB: ' . json_encode($response_array));
      return $response_array;
    }
    else {
      throw new PaymentGatewayException('Communication failure with CIB server.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    if (!$amount) {
      $amount = $payment->getAmount();
    }
    // Sanity checks. The minimal refund amount is 100 HUF.
    if ($amount->getCurrencyCode() !== 'HUF') {
      throw new PaymentGatewayException('Only HUF refund is supported.');
    }
    $price_100 = new Price(100, 'HUF');
    if ($amount->lessThan($price_100)) {
      throw new PaymentGatewayException('The minimal refund amount is 100 HUF.');
    }

    // Get transaction status.
    $msgt71 = $this->simpleMsgt($payment, 70);

    // A status of 10 means the transaction has been authorized but not
    // captured. Only the full amount can be refunded.
    switch ($msgt71['STATUS']) {
      // A status of 10 means an authorized transaction.
      case '10':
        if (!$amount->equals($payment->getAmount())) {
          throw new PaymentGatewayException('The transaction is not captured yet, so only full refund is possible. Try again later.');
        }
        $this->simpleMsgt($payment, 74);
        $post_refund_msgt71 = $this->simpleMsgt($payment, 70);
        if (!in_array($post_refund_msgt71['STATUS'], [40, 60])) {
          throw new PaymentGatewayException('Unsuccessful refund.');
        }
        $payment->setState('voided')->save();
        break;

      // A status of 30 means a captured transaction.
      case '30':
        // Set the amount to refund.
        $config = $payment->getPaymentGateway()->getPluginConfiguration();
        $msgt80 = [
          'MSGT' => 80,
          'PID' => $config['pid'],
          'TRID' => $payment->getRemoteId(),
          'AMOORIG' => $msgt71['CURAMO2'],
          'AMONEW' => (int) $amount->getNumber(),
        ];
        $msgt81 = $this->sendRequest($msgt80);

        // Send refund request.
        $this->simpleMsgt($payment, 78);
        $msgt71_final = $this->simpleMsgt($payment, 70);
        if (!in_array($msgt71_final['STATUS'], [50, 60])) {
          throw new PaymentGatewayException('Unsuccessful refund.');
        }
        if ($amount->lessThan($payment->getAmount())) {
          $payment->setState('partially_refunded')->save();
        }
        else {
          $payment->setState('refunded')->save();
        }
        break;

      case '0':
        throw new PaymentGatewayException('Transaction not authorized yet. Refund is not possible.');
        break;

      case '02':
        throw new PaymentGatewayException('The minimal refund amount is 100 HUF. Refund is not possible.');
        break;

      case '40':
        throw new PaymentGatewayException('Transaction capture undone before end of day closing. Refund is not possible.');
        break;

      case '50':
        throw new PaymentGatewayException('Transaction refunded after capture. Refund is not possible.');
        break;

      case '60':
        throw new PaymentGatewayException('Transaction closed. Refund is not possible.');
        break;

      case '99':
        throw new PaymentGatewayException('Wrong request. Refund is not possible.');
        break;
    }
  }

}

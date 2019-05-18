<?php

namespace Drupal\gopay\Payment;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\gopay\Contact\ContactInterface;
use Drupal\gopay\Item\ItemInterface;
use GoPay\Definition\Payment\PaymentInstrument;
use Drupal\gopay\GoPayApiInterface;
use Drupal\gopay\Exception\GoPayInvalidSettingsException;
use GoPay\Definition\Language;

/**
 * Class StandardPayment.
 *
 * @package Drupal\gopay\Payment
 */
class StandardPayment implements PaymentInterface {

  /**
   * ConfigFactory Service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * GoPayApi Service.
   *
   * @var \Drupal\gopay\GoPayApiInterface
   */
  protected $goPayApi;

  /**
   * Return URL.
   *
   * @var string
   */
  protected $returnUrl;

  /**
   * Notification URL.
   *
   * @var string
   */
  protected $notificationUrl;

  /**
   * Default payment instrument.
   *
   * @var string
   */
  protected $defaultPaymentInstrument;

  /**
   * Allowed payment instrument.
   *
   * @var array|string
   */
  protected $allowedPaymentInstruments;

  /**
   * Payer contact object.
   *
   * @var \Drupal\gopay\Contact\ContactInterface
   */
  protected $payerContact;

  /**
   * Additional API parameters.
   *
   * @var array
   */
  protected $additionalParams;

  /**
   * Items in payment.
   *
   * @var array|\Drupal\gopay\Item\ItemInterface
   */
  protected $items;

  /**
   * Amount of payment.
   *
   * @var int
   */
  protected $amount;

  /**
   * Currency of payment.
   *
   * @var string
   */
  protected $currency;

  /**
   * Order number.
   *
   * @var int
   */
  protected $orderNumber;

  /**
   * Order description.
   *
   * @var string
   */
  protected $orderDescription;

  /**
   * Payment language.
   *
   * @var string
   *
   * @see https://doc.gopay.com/cs/#lang
   */
  protected $lang;

  /**
   * GoPayFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   ConfigFactory service.
   * @param \Drupal\gopay\GoPayApiInterface $go_pay_api
   *   GoPayApi service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, GoPayApiInterface $go_pay_api) {
    $this->configFactory = $config_factory;
    $this->goPayApi = $go_pay_api;

    // Set defaults.
    $settings = $this->configFactory->get('gopay.settings');

    $this->returnUrl = $settings->get('return_callback');
    if (!$this->returnUrl) {
      $this->returnUrl = Url::fromRoute('gopay.return')->setAbsolute()->toString();
    }

    $this->notificationUrl = $settings->get('notification_callback');
    if (!$this->notificationUrl) {
      $this->notificationUrl = Url::fromRoute('gopay.notification')->setAbsolute()->toString();
    }

    $this->defaultPaymentInstrument = $settings->get('default_payment_instrument');
    if (!$this->defaultPaymentInstrument) {
      $this->defaultPaymentInstrument = PaymentInstrument::PAYMENT_CARD;
    }

    $this->allowedPaymentInstruments = $settings->get('allowed_payment_instruments');
    if (!$this->allowedPaymentInstruments) {
      $this->allowedPaymentInstruments = [PaymentInstrument::PAYMENT_CARD, PaymentInstrument::BANK_ACCOUNT];
    }

    // TODO - add this configuration to administration form.
    $this->lang = Language::CZECH;

    // Contact is not needed.
    $this->payerContact = NULL;

    $this->items = [];

    // Random order number is provided by GoPay if not specified.
    $this->orderNumber = NULL;

    // These are mandatory properties without default values.
    $this->amount = NULL;
    $this->currency = NULL;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setReturnUrl($url) {
    $this->returnUrl = $url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setNotificationUrl($url) {
    $this->notificationUrl = $url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultPaymentInstrument($payment_instrument) {
    $this->defaultPaymentInstrument = $payment_instrument;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAllowedPaymentInstruments($payment_instruments) {
    $this->allowedPaymentInstruments = $payment_instruments;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setContact(ContactInterface $contact) {
    $this->payerContact = $contact;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addAdditionalParam(array $additional_param) {
    if (!isset($additional_param['name']) || !isset($additional_param['value'])) {
      throw new GoPayInvalidSettingsException('You must set "name" and "value" for additional parameter.');
    }

    $this->additionalParams[] = $additional_param;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addItem(ItemInterface $item) {
    $this->items[] = $item;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount($amount, $in_cents = TRUE) {
    if (!$in_cents) {
      $amount *= 100;
    }
    $this->amount = $amount;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrency($currency) {
    $this->currency = $currency;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderNumber($order_number) {
    $this->orderNumber = $order_number;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderDescription($order_description) {
    $this->orderDescription = $order_description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLang($lang) {
    $this->lang = $lang;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $config = $this->configFactory->get('gopay.settings');
    $ret = [];

    // Check for mandatory.
    if (!$this->currency) {
      throw new GoPayInvalidSettingsException('You must set currency in payment.');
    }
    if (!$this->amount) {
      throw new GoPayInvalidSettingsException('You must set amount in payment.');
    }

    // Set callbacks.
    $ret['callback'] = [
      'return_url' => $this->returnUrl,
      'notification_url' => $this->notificationUrl,
    ];

    // Set payer.
    $ret['payer'] = [
      'default_payment_instrument' => $this->defaultPaymentInstrument,
      // Remove empty values from array and get values, because API accepts
      // ONLY array of strings, without specific keys and empty values.
      'allowed_payment_instruments' => array_values(array_filter($this->allowedPaymentInstruments)),
    ];

    // Set payer contact.
    if ($this->payerContact) {
      $ret['payer']['contact'] = $this->payerContact->toArray();
    }

    // Set target.
    $ret['target'] = [
      'type' => 'ACCOUNT',
      'goid' => $config->get('go_id'),
    ];

    // Order information.
    $ret['amount'] = $this->amount;
    $ret['currency'] = $this->currency;
    $ret['order_number'] = $this->orderNumber;
    $ret['order_description'] = $this->orderDescription;
    $ret['lang'] = $this->lang;

    // Items.
    foreach ($this->items as $item) {
      $ret['items'][] = $item->toArray();
    }

    // Additional params.
    if ($this->additionalParams) {
      foreach ($this->additionalParams as $param) {
        $ret['additional_params'][] = $param;
      }
    }

    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function buildLink($text = NULL) {
    return $this->goPayApi->buildLink($this, $text);
  }

  /**
   * {@inheritdoc}
   */
  public function buildInlineForm($text = NULL) {
    return $this->goPayApi->buildInlineForm($this, $text);
  }

}

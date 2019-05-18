<?php

namespace Drupal\commerce_sermepa\PluginForm\OffsiteRedirect;

use CommerceRedsys\Payment\Sermepa as SermepaApi;
use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Sermepa/Redsýs class for the payment form.
 */
class SermepaForm extends BasePaymentOffsiteForm implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new SermepaForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(MessengerInterface $messenger, TimeInterface $time, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('datetime.time'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    $order = $payment->getOrder();

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    // Get the gateway settings.
    $gateway_settings = $payment_gateway_plugin->getConfiguration();

    // Create a new instance of the Sermepa library and initialize it.
    try {
      $gateway = new SermepaApi($gateway_settings['merchant_name'], $gateway_settings['merchant_code'], $gateway_settings['merchant_terminal'], $gateway_settings['merchant_password'], $payment_gateway_plugin->getMode());

      // Configure the gateway transaction.
      $date = DrupalDateTime::createFromTimestamp($this->time->getRequestTime());

      $parameters = FALSE;
      // Check if the payment currency code and the payment method settings are
      // the same.
      $currency_code = $payment->getAmount()->getCurrencyCode();
      /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
      $currency = $this->entityTypeManager->getStorage('commerce_currency')->load($currency_code);

      if ($currency->getNumericCode() == $gateway_settings['currency']) {
        // Prepare the amount converting 120.00 or 120 to 12000.
        $amount = $payment->getAmount()->multiply(100)->getNumber();
        $gateway->setAmount($amount)
          ->setCurrency($gateway_settings['currency'])
          ->setOrder(substr($date->format('ymdHis') . 'Id' . $order->id(), -12, 12))
          ->setMerchantMerchantGroup($gateway_settings['merchant_group'])
          ->setConsumerLanguage($gateway_settings['merchant_consumer_language'])
          ->setMerchantData($order->id())
          ->setTransactionType($gateway_settings['transaction_type'])
          ->setMerchantURL($payment_gateway_plugin->getNotifyUrl()->toString())
          ->setUrlKO($form['#cancel_url'])
          ->setUrlOK($form['#return_url']);

        // Get the transaction fields for the sermepa form.
        $parameters = $gateway->composeMerchantParameters();
      }
    }
    catch (\Exception $exception) {
      watchdog_exception('commerce_sermepa', $exception);
    }

    if (empty($parameters)) {
      $this->messenger->addError($this->t('An error has been occurred trying of process the payment data, please contact with us.'));

      return $this->redirectToPaymentInformationPane($order);
    }

    $data = [
      'Ds_SignatureVersion' => $gateway->getSignatureVersion(),
      'Ds_MerchantParameters' => $parameters,
      'Ds_Signature' => $gateway->composeMerchantSignature(),
    ];

    return $this->buildRedirectForm($form, $form_state, $gateway->getEnvironment(), $data, BasePaymentOffsiteForm::REDIRECT_POST);
  }

  /**
   * Redirects to the payment information pane on error.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @see \Drupal\commerce_payment\Plugin\Commerce\CheckoutPane\PaymentProcess::getErrorStepId()
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  protected function redirectToPaymentInformationPane(OrderInterface $order) {
    try {
      /** @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesInterface $checkout_flow */
      $checkout_flow = $order->get('checkout_flow')->first()->get('entity')->getTarget()->getValue()->getPlugin();
      $step_id = $checkout_flow->getPane('payment_information')->getStepId();
      if ($step_id == '_disabled') {
        // Can't redirect to the _disabled step. This could mean that
        // isVisible() was overridden to allow PaymentProcess to be used without
        // a payment_information pane, but this method was not modified.
        throw new \RuntimeException('Cannot get the step ID for the payment_information pane. The pane is disabled.');
      }

      $checkout_flow->redirectToStep($step_id);
    }
    catch (\Exception $exception) {
      $redirect_url = Url::fromRoute('<front>', ['absolute' => TRUE])->toString();

      throw new NeedsRedirectException($redirect_url);
    }
  }

}

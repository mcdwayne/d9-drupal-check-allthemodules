<?php

namespace Drupal\commerce_instamojo\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Instamojo\Instamojo;

/**
 * Class PaymentOffsiteForm.
 *
 * @package Drupal\commerce_instamojo\PluginForm\OffsiteRedirect
 */
class PaymentOffsiteForm extends BasePaymentOffsiteForm implements ContainerInjectionInterface {

  const INSTAMOJO_TEST_API_URL = 'https://test.instamojo.com/api/1.1/';
  const INSTAMOJO_LIVE_API_URL = 'https://www.instamojo.com/api/1.1/payment-requests/';

  /**
   * Stores runtime messages sent out to individual users on the page.
   *
   * @var Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Logger channel interface.
   *
   * @var Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * PaymentOffsiteForm constructor.
   *
   * @param Drupal\Core\Messenger\MessengerInterface $messenger
   *   Stores runtime messages sent out to individual users on the page.
   * @param Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger channel interface.
   */
  public function __construct(MessengerInterface $messenger, LoggerChannelInterface $logger) {
    $this->messenger = $messenger;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('commerce_instamojo.logger.channel.instamojo_log')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();

    $api = new Instamojo($config['api_key'], $config['auth_token'],
      $config['mode'] == 'test' ? self::INSTAMOJO_TEST_API_URL : self::INSTAMOJO_LIVE_API_URL);

    try {

      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      $payment = $this->entity;

      $order_id = $payment->getOrderId();

      $order = Order::load($order_id);

      $address = $order->getBillingProfile()->address->first();

      $payload = $api->paymentRequestCreate(
        [
          "purpose" => $config['order_prefix'] . $payment->getOrderId(),
          "amount" => round($payment->getAmount()->getNumber(), 2),
          "buyer_name" => $address->getGivenName(),
          "send_email" => $config['send_email'],
          "allow_repeated_payments" => $config['allow_repeated_payments'],
          "email" => $order->getEmail(),
          "redirect_url" => Url::FromRoute('commerce_payment.checkout.return', ['commerce_order' => $order_id, 'step' => 'payment'], ['absolute' => TRUE])->toString(),
        ]
      );

      $response = new RedirectResponse($payload['longurl']);
      $response->send();
      return [];
    }
    catch (InvalidValueException $e) {
      $this->logger->error($e->getMessage());
    }
    catch (\Exception $e) {
      $this->messenger->addError('Unexpected error. Please contact store administration if the problem persists.');
      if ($config['watchdog_log']) {
        $this->logger->error('Unexpected error. Please contact store administration if the problem persists.');
      }
    }

    return [];
  }

  /**
   * Returns configuration data.
   *
   * @return array
   *   Configuration array.
   */
  private function getConfiguration() {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_quickpay_gateway\Plugin\Commerce\PaymentGateway\RedirectCheckout $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    return $payment_gateway_plugin->getConfiguration();
  }

}

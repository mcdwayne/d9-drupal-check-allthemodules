<?php

namespace Drupal\commerce_vipps\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\commerce_vipps\Event\InitiatePaymentOptionsEvent;
use Drupal\commerce_vipps\Event\VippsEvents;
use Drupal\commerce_vipps\Resolver\ChainOrderIdResolverInterface;
use Drupal\commerce_vipps\VippsManager;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class VippsCheckoutForm.
 *
 * Handles the initiation of vipps payments.
 */
class VippsLandingPageRedirectForm extends BasePaymentOffsiteForm implements ContainerInjectionInterface {

  /**
   * @var \Drupal\commerce_vipps\VippsManager
   */
  protected $vippsManager;

  /**
   * @var \Drupal\commerce_vipps\Resolver\ChainOrderIdResolverInterface
   */
  protected $chainOrderIdResolver;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * VippsLandingPageRedirectForm constructor.
   *
   * @param \Drupal\commerce_vipps\VippsManager $vippsManager
   * @param \Drupal\commerce_vipps\Resolver\ChainOrderIdResolverInterface $chainOrderIdResolver
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   */
  public function __construct(VippsManager $vippsManager, ChainOrderIdResolverInterface $chainOrderIdResolver, EventDispatcherInterface $eventDispatcher) {
    $this->vippsManager = $vippsManager;
    $this->chainOrderIdResolver = $chainOrderIdResolver;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_vipps.manager'),
      $container->get('commerce_vipps.chain_order_id_resolver'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    // When dumping here, we have a new entity, use that by default.
    $payment = $this->entity;
    /** @var \Drupal\commerce_vipps\Plugin\Commerce\PaymentGateway\Vipps $plugin */
    $plugin = $payment->getPaymentGateway()->getPlugin();
    $settings = $payment->getPaymentGateway()->getPluginConfiguration();

    // Create payment.
    $payment->setRemoteId($settings['prefix'] . $this->chainOrderIdResolver->resolve());

    // Save order.
    $order = $payment->getOrder();
    $order_changed = FALSE;
    if ($order->getData('vipps_auth_key') === NULL) {
      $order->setData('vipps_auth_key', $this->generateAuthToken());
      $order_changed = TRUE;
    }

    if ($order->getData('vipps_current_transaction') !== $payment->getRemoteId()) {
      $order->setData('vipps_current_transaction', $payment->getRemoteId());
      $order_changed = TRUE;
    }

    $options = [
      'authToken' => $order->getData('vipps_auth_key'),
    ];

    // Set options.
    $event = new InitiatePaymentOptionsEvent($payment, $options);
    $this->eventDispatcher->dispatch(VippsEvents::INITIATE_PAYMENT_OPTIONS, $event);
    $options = $event->getOptions();

    try {
      $url = $this->vippsManager
        ->getPaymentManager($plugin)
        ->initiatePayment(
          $payment->getRemoteId(),
          (int) $payment->getAmount()->multiply(100)->getNumber(),
          $this->t('Payment for order @order_id', ['@order_id' => $payment->getOrderId()]),
          // Get standard payment notification callback and add
          rtrim($plugin->getNotifyUrl()->toString(), '/') . '/' . $payment->getOrderId(),
          $form['#return_url'],
          $options
        )
        ->getURL();
    }
    catch (\Exception $exception) {
      throw new PaymentGatewayException($exception->getMessage());
    }

    // If the payment was successfully created at remote host
    $payment->save();
    if ($order_changed === TRUE) {
      $order->save();
    }

    return $this->buildRedirectForm($form, $form_state, $url, []);
  }

  /**
   * Method to generate access token.
   *
   * @return string
   */
  private function generateAuthToken() {
    try {
      $randomStr = random_bytes(16);
    } catch (\Exception $e) {
      $randomStr = uniqid('', true);
    }
    return bin2hex($randomStr);
  }

}

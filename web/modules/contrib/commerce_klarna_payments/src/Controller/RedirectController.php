<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Controller;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_checkout\CheckoutOrderManager;
use Drupal\commerce_klarna_payments\Klarna\Exception\FraudException;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Klarna\Rest\Transport\Exception\ConnectorException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handle Klarna payment redirects.
 */
class RedirectController implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  protected $checkoutOrderManager;
  protected $messenger;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\commerce_checkout\CheckoutOrderManager $checkoutOrderManager
   *   The checkout order manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(CheckoutOrderManager $checkoutOrderManager, MessengerInterface $messenger) {
    $this->checkoutOrderManager = $checkoutOrderManager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_checkout.checkout_order_manager'),
      $container->get('messenger')
    );
  }

  /**
   * Validates the authorization and handles the redirects.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order.
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $commerce_payment_gateway
   *   The payment gateway.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function handleRedirect(OrderInterface $commerce_order, PaymentGatewayInterface $commerce_payment_gateway, Request $request) {
    /** @var \Drupal\commerce_klarna_payments\Plugin\Commerce\PaymentGateway\Klarna $plugin */
    $plugin = $commerce_payment_gateway->getPlugin();

    $query = $request->request->all();
    $values = NestedArray::getValue($query, ['payment_process', 'offsite_payment']);

    if (empty($values['klarna_authorization_token'])) {
      $plugin->getLogger()->error(
        $this->t('Authorization token not set for #@id', [
          '@id' => $commerce_order->id(),
        ])
      );
      $this->messenger->addError(
        $this->t('Authorization token not set. This should only happen when Klarna order is incomplete.')
      );

      // Redirect back to review step.
      $this->redirectOnFailure($commerce_order);
    }

    try {
      $request = $plugin->getKlarnaConnector()->authorizeRequest($commerce_order);
      $response = $plugin->getKlarnaConnector()
        ->authorizeOrder($request, $commerce_order, $values['klarna_authorization_token']);

      throw new NeedsRedirectException($response->getRedirectUrl());
    }
    catch (\InvalidArgumentException | ConnectorException $e) {

      $plugin->getLogger()->critical(
        $this->t('Authorization validation failed for #@id: @message', [
          '@id' => $commerce_order->id(),
          '@message' => $e->getMessage(),
        ])
      );

      $this->messenger->addError(
        $this->t('Authorization validation failed. Please contact store administration if the problemn persists.')
      );

      // Redirect back to review step.
      $this->redirectOnFailure($commerce_order);
    }
    catch (FraudException $e) {
      $plugin->getLogger()->critical(
        $this->t('Fraudulent order validation failed for order #@id: @message', [
          '@id' => $commerce_order->id(),
          '@message' => $e->getMessage(),
        ])
      );

      $this->messenger->addError(
        $this->t('Fraudulent order detected. Please contact store administration if the problemn persists.')
      );

      // Redirect back to review step.
      $this->redirectOnFailure($commerce_order);
    }
  }

  /**
   * Redirects on back to previous step on failure.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  private function redirectOnFailure(OrderInterface $order) : void {
    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
    $checkout_flow = $order->get('checkout_flow')->entity;
    $checkout_flow_plugin = $checkout_flow->getPlugin();

    $step_id = $this->checkoutOrderManager->getCheckoutStepId($order);

    $checkout_flow_plugin->redirectToStep($checkout_flow_plugin->getPreviousStepId($step_id));
  }

}

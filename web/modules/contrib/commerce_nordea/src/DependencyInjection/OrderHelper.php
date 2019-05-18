<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2018 Lamia Oy (https://lamia.fi)
 */


namespace Drupal\commerce_nordea\DependencyInjection;


use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_price\Price;
use Drupal\commerce_nordea\Plugin\Commerce\PaymentGateway\NordeaPayment;
use Verifone\Core\DependencyInjection\Configuration\Backend\BackendConfigurationImpl;
use Verifone\Core\DependencyInjection\CoreResponse\PaymentStatusImpl;
use Verifone\Core\DependencyInjection\Service\OrderImpl;
use Verifone\Core\DependencyInjection\Service\TransactionImpl;
use Verifone\Core\DependencyInjection\Transporter\CoreResponse;
use Verifone\Core\Executor\BackendServiceExecutor;
use Verifone\Core\ExecutorContainer;
use Verifone\Core\Service\Backend\GetPaymentStatusService;
use Verifone\Core\Service\Backend\ListTransactionNumbersService;
use Verifone\Core\ServiceFactory;

class OrderHelper
{

  /**
   * @var NordeaPayment
   */
  public $paymentGateway = null;

  /**
   * @var string
   */
  public $paymentGatewayId = null;

  /**
   * @var PaymentHelper
   */
  public $paymentHelper = null;

  public function checkOrderStatus(OrderInterface $order)
  {
    $this->getPaymentGateway($order);
    $this->getPaymentHelper();

    try {
      $this->checkPaymentStatus($order);
    } catch (\Exception $e) {
      // skip for next check
    }

  }

  public function checkPaymentStatus(OrderInterface $order)
  {
    $response = $this->getTransactionsFromGate($order);

    if (null === $response) {
      return false;
    }

    $totalPaid = 0;

    /** @var TransactionImpl $item */
    foreach ($response as $item) {
      $transactionCode = $item->getMethodCode();
      $transactionNumber = $item->getNumber();

      $gatewayId = $order->get('payment_gateway')->first()->entity->id();

      /** @var PaymentStatusImpl $transaction */
      $transaction = $this->getPaymentStatus($gatewayId, $transactionCode, $transactionNumber);

      if (null !== $transaction) {
        $transactions[] = $transaction;

        $totalPaid += $transaction->getOrderAmount();

        if ($this->confirmPayment($transaction->getCode())) {
          $this->finalizeOrder($order, $transaction);
          return true;
        } elseif ($transaction->getCode() === 'cancelled') {
          return false;
        }
      }

      if ($totalPaid >= $order['total']) {
        return true;
      }

    }

    return null;
  }

  public function getTransactionsFromGate(OrderInterface $order)
  {
    $orderImpl = new OrderImpl((string)$order->id(), '', '', '', '', '');

    $configuration = $this->paymentGateway->getConfiguration();
    $defaultConfiguration = $this->paymentGateway->defaultConfiguration();

    $gatewayId = $order->get('payment_gateway')->first()->entity->id();

    $shopKeyFile = $this->paymentHelper->getKeyPath($gatewayId, $configuration, $defaultConfiguration, $this->paymentHelper::KEY_FILE_SHOP);

    $configObject = new BackendConfigurationImpl(
      $shopKeyFile,
      $this->paymentHelper->getMerchantId($gatewayId, $configuration, $defaultConfiguration),
      $this->paymentHelper->getSystemName(),
      $this->paymentHelper->getModuleVersion(),
      $this->paymentHelper->getUrls($configuration, 'server'),
      $configuration['disable_rsa_blinding']
    );

    /** @var ListTransactionNumbersService $service */
    $service = ServiceFactory::createService($configObject, 'Backend\ListTransactionNumbersService');
    $service->insertOrder($orderImpl);

    $container = new ExecutorContainer();

    /** @var BackendServiceExecutor $exec */
    $exec = $container->getExecutor('backend');

    $gatewayKeyFile = $this->paymentHelper->getKeyPath($gatewayId, $configuration, $defaultConfiguration, $this->paymentHelper::KEY_FILE_GATEWAY);

    /** @var CoreResponse $response */
    $response = $exec->executeService($service, $gatewayKeyFile);

    if ($response->getStatusCode()) {
      return $response->getBody();
    } else {
      return null;
    }
  }

  public function getPaymentStatus($gatewayId, $paymentMethod, $transactionNumber)
  {
    $transaction = new TransactionImpl($paymentMethod, $transactionNumber);

    $configuration = $this->paymentGateway->getConfiguration();
    $defaultConfiguration = $this->paymentGateway->defaultConfiguration();

    $shopKeyFilePath = $this->paymentHelper->getKeyPath($gatewayId, $configuration, $defaultConfiguration, $this->paymentHelper::KEY_FILE_SHOP);

    $configObject = new BackendConfigurationImpl(
      $shopKeyFilePath,
      $this->paymentHelper->getMerchantId($gatewayId, $configuration, $defaultConfiguration),
      $this->paymentHelper->getSystemName(),
      $this->paymentHelper->getModuleVersion(),
      $this->paymentHelper->getUrls($configuration, 'server'),
      $configuration['disable_rsa_blinding']
    );

    /** @var GetPaymentStatusService $service */
    $service = ServiceFactory::createService($configObject, 'Backend\GetPaymentStatusService');
    $service->insertTransaction($transaction);

    $container = new ExecutorContainer();

    /** @var BackendServiceExecutor $exec */
    $exec = $container->getExecutor('backend');

    $gatewayKeyFile = $this->paymentHelper->getKeyPath($gatewayId, $configuration, $defaultConfiguration, $this->paymentHelper::KEY_FILE_GATEWAY);

    /** @var CoreResponse $response */
    $response = $exec->executeService($service, $gatewayKeyFile);

    if ($response->getStatusCode()) {
      return $response->getBody();
    } else {
      return null;
    }
  }

  public function finalizeOrder(OrderInterface $order, PaymentStatusImpl $responseBody)
  {

    $trans_id = preg_replace("/[^0-9]+/", "", $responseBody->getTransactionNumber());
    $_transactionId = $responseBody->getTransactionNumber();

    $paymentMethod = $responseBody->getPaymentMethodCode();

    $paymentId = $_transactionId . NordeaPayment::TRANSACTION_ID_DELIMITER . $paymentMethod;

    // create payment
    $payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
    $existing_payments = $payment_storage->loadMultipleByOrder($order);

    $payment_logged = FALSE;
    foreach ($existing_payments as $payment) {
      if ($payment->getRemoteId() == $paymentId) {
        $payment_logged = TRUE;
        break;
      }
    }

    $amount = $responseBody->getOrderAmount() / 100;

    if (!$payment_logged) {
      $payment = $payment_storage->create([
        'state' => 'completed',
        'amount' => new Price((string)$amount, $order->getTotalPrice()->getCurrencyCode()),
        'payment_gateway' => $this->paymentGatewayId,
        'order_id' => $order->id(),
        'remote_id' => $paymentId,
        'completed' => \Drupal::time()->getRequestTime(),
      ]);



      $payment->save();

      $transition = $order->getState()->getWorkflow()->getTransition('place');
      $order->getState()->applyTransition($transition);
      $order->save();

    }

  }

  /**
   * @param OrderInterface $order
   * @return NordeaPayment
   */
  public function getPaymentGateway(OrderInterface $order)
  {
    if ($this->paymentGateway === null) {
      /** @var NordeaPayment $paymentGateway */
      $tmp = $order->get('payment_gateway')->getValue();
      $paymentGatewayId = $tmp[0]['target_id'];

      $this->paymentGatewayId = $paymentGatewayId;

      $this->paymentGateway = PaymentGateway::load($paymentGatewayId)->getPlugin();
    }

    return $this->paymentGateway;
  }

  /**
   * @return PaymentHelper
   */
  public function getPaymentHelper()
  {
    if ($this->paymentHelper === null) {
      $this->paymentHelper = new PaymentHelper();
    }
    return $this->paymentHelper;
  }

  public function confirmPayment($status)
  {
    $confirm = ['committed', 'settled', 'verified'];

    return in_array($status, $confirm);
  }
}
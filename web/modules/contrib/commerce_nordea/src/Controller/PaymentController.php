<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2018 Lamia Oy (https://lamia.fi)
 */


namespace Drupal\commerce_nordea\Controller;

use Composer\Autoload\ClassLoader;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_nordea\DependencyInjection\OrderLockerHelper;
use Drupal\commerce_nordea\DependencyInjection\PaymentHelper;
use Drupal\commerce_nordea\Plugin\Commerce\PaymentGateway\NordeaPayment;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\TypedData\Plugin\DataType\Uri;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use \Verifone\Core\DependencyInjection\Service\OrderImpl;
use \Verifone\Core\ServiceFactory;
use \Verifone\Core\Service\FrontendResponse\FrontendResponseServiceImpl;
use \Verifone\Core\DependencyInjection\Transporter\CoreResponse;
use \Verifone\Core\ExecutorContainer;
use \Verifone\Core\DependencyInjection\CoreResponse\PaymentResponseImpl;
use \Verifone\Core\Converter\Response\CoreResponseConverter;
use Drupal\commerce_price\Price;


class PaymentController extends ControllerBase
{

  const RETRY_DELAY_IN_SECONDS = 2;
  const RETRY_MAX_ATTEMPTS = 5;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var TimeInterface
   */
  protected $time;

  /** @var PaymentHelper */
  protected $_paymentHelper;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    TimeInterface $time,
    PaymentHelper $paymentHelper

  )
  {
    $this->entityTypeManager = $entityTypeManager;
    $this->time = $time;
    $this->_paymentHelper = $paymentHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('datetime.time'),
      $container->get('commerce_nordea.payment_helper')
    );
  }

  public function successDelayed(Request $request)
  {

    $orderLocker = new OrderLockerHelper();

    $params = $request->request->all();

    /** @var FrontendResponseServiceImpl $service */
    $service = ServiceFactory::createResponseService($params);

    $orderNumber = $service->getOrderNumber();

    if (empty($orderNumber)) {
      throw new PaymentGatewayException('Payment failed');
    }

    /** @var OrderInterface $order */
    $order = Order::load($orderNumber);

    if (null === $order) {
      throw new PaymentGatewayException('Payment failed');
    }

    if ($order->getState()->getValue()['value'] === 'completed') {
      // Don't need to process paid order.
      // Order changed to paid by delayed functionality

      if ($orderLocker->isLockedOrder($orderNumber)) {
        $orderLocker->unlockOrder($orderNumber);
      }

      header("HTTP/1.1 200 OK");
      die('<html><head><meta http-equiv="refresh" content="0;url=' . Url::fromRoute('commerce_cart.page', [], ['absolute' => TRUE])->toString() . '"></head></html>');

    }

    $attempts = 0;
    while (!$orderLocker->lockOrder($orderNumber) && $attempts < self::RETRY_MAX_ATTEMPTS) {
      sleep(self::RETRY_DELAY_IN_SECONDS);
      ++$attempts;
    }

    if ($attempts > 0 && $attempts < self::RETRY_MAX_ATTEMPTS) {

      $orderTmp = \Drupal\commerce_order\Entity\Order::load($order->id());

      if ($orderTmp && $orderTmp->getState()->getValue()['value'] === 'completed') {
        // Don't need to process paid order.
        // Order changed to paid by delayed functionality

        if ($orderLocker->isLockedOrder($orderNumber)) {
          $orderLocker->unlockOrder($orderNumber);
        }

        header("HTTP/1.1 200 OK");
        die('<html><head><meta http-equiv="refresh" content="0;url=' . Url::fromRoute('commerce_cart.page', [], ['absolute' => TRUE])->toString() . '"></head></html>');

      }
    }

    $gatewayId = $order->get('payment_gateway')->first()->entity->id();

    try {

      $totalTax = 0;

      foreach ($order->collectAdjustments() as $adjustment) {
        if ($adjustment->getType() === 'tax') {
          $totalTax += $adjustment->getAmount()->getNumber();
        }
      }

      $totalInclTax = $order->getTotalPrice()->getNumber();
      $totalExclTax = $totalInclTax - $totalTax;

      // order information
      $orderImpl = new OrderImpl(
        (string)$order->id(),
        gmdate('Y-m-d H:i:s', $order->getCreatedTime()),
        $this->_paymentHelper->convertCountryToISO4217($order->getTotalPrice()->getCurrencyCode()),
        (string)(round($totalInclTax, 2) * 100),
        (string)(round($totalExclTax, 2) * 100),
        (string)(round($totalTax, 2) * 100)
      );

      /** @var FrontendResponseServiceImpl $service */
      $service->insertOrder($orderImpl);
      $container = new ExecutorContainer(array('responseConversion.class' => 'Converter\Response\FrontendServiceResponseConverter'));
      $exec = $container->getExecutor(ExecutorContainer::EXECUTOR_TYPE_FRONTEND_RESPONSE);

      /** @var NordeaPayment $paymentGateway */
      $tmp = $order->get('payment_gateway')->getValue();
      $paymentGatewayId = $tmp[0]['target_id'];

      $paymentGateway = PaymentGateway::load($paymentGatewayId)->getPlugin();

      $gatewayKeyFile = $this->_paymentHelper->getKeyPath($gatewayId, $paymentGateway->getConfiguration(), $paymentGateway->defaultConfiguration(), $this->_paymentHelper::KEY_FILE_GATEWAY);

      /** @var CoreResponse $parseResponse */
      $parsedResponse = $exec->executeService($service, $gatewayKeyFile);

      /** @var PaymentResponseImpl $body */
      $responseBody = $parsedResponse->getBody();
      $validate = true;
    } catch (\Exception $e) {
      throw new PaymentGatewayException('Payment failed');
    }

    if ($validate
      && $parsedResponse->getStatusCode() == CoreResponseConverter::STATUS_OK
      && empty($responseBody->getCancelMessage())
    ) {

      $trans_id = preg_replace("/[^0-9]+/", "", $responseBody->getTransactionNumber());
      $_transactionId = $responseBody->getTransactionNumber();

      $paymentMethod = $responseBody->getPaymentMethodCode();

      $paymentId = $_transactionId . NordeaPayment::TRANSACTION_ID_DELIMITER . $paymentMethod;

      // create payment
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      $existing_payments = $payment_storage->loadMultipleByOrder($order);

      $payment_logged = FALSE;
      foreach ($existing_payments as $payment) {
        if ($payment->getRemoteId() == $paymentId) {
          $payment_logged = TRUE;
          break;
        }
      }

      $amount = $responseBody->getOrderGrossAmount() / 100;

      if (!$payment_logged) {
        $payment = $payment_storage->create([
          'state' => 'completed',
          'amount' => new Price((string)$amount, $order->getTotalPrice()->getCurrencyCode()),
          'payment_gateway' => $paymentGatewayId,
          'order_id' => $order->id(),
          'remote_id' => $paymentId,
          'completed' => $this->time->getRequestTime(),
        ]);

        $payment->save();

        $transition = $order->getState()->getWorkflow()->getTransition('place');
        $order->getState()->applyTransition($transition);
        $order->save();

        $orderLocker->unlockOrder($orderNumber);

      }

    } else {
      throw new PaymentGatewayException('Payment failed');
    }

    header("HTTP/1.1 200 OK");
    die('<html><head><meta http-equiv="refresh" content="0;url=' . Url::fromRoute('commerce_cart.page', [], ['absolute' => TRUE])->toString() . '"></head></html>');

  }

}
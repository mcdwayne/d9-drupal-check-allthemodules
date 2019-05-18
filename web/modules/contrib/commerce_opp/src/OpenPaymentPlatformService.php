<?php

namespace Drupal\commerce_opp;

use Drupal\commerce_opp\Transaction\Status\Pending;
use Drupal\commerce_opp\Transaction\Status\Rejected;
use Drupal\commerce_opp\Transaction\Status\SuccessOrPending;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Default Open Payment Platform service implementation.
 */
class OpenPaymentPlatformService implements OpenPaymentPlatformServiceInterface {

  /**
   * The payment storage.
   *
   * @var \Drupal\commerce_payment\PaymentStorageInterface
   */
  protected $paymentStorage;

  /**
   * The payment gateway storage.
   *
   * @var \Drupal\commerce_payment\PaymentGatewayStorageInterface
   */
  protected $paymentGatewayStorage;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new OpenPaymentPlatformService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TimeInterface $time) {
    $this->paymentStorage = $entity_type_manager->getStorage('commerce_payment');
    $this->paymentGatewayStorage = $entity_type_manager->getStorage('commerce_payment_gateway');
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function getOppGatewayIds($only_active = TRUE) {
    $query = $this->paymentGatewayStorage->getQuery();
    $opp_plugin_ids = [
      'opp_copyandpay_bank',
      'opp_copyandpay_card',
      'opp_copyandpay_virtual',
    ];
    $query->condition('plugin', $opp_plugin_ids, 'IN');
    if ($only_active) {
      $query->condition('status', TRUE);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteExpiredAuthorizations() {
    $opp_gateways = $this->getOppGatewayIds();
    if (empty($opp_gateways)) {
      return;
    }
    $query = $this->paymentStorage->getQuery();
    $query->condition('payment_gateway', $opp_gateways, 'IN');
    $query->condition('state', 'authorization');
    $query->condition('expires', $this->time->getRequestTime(), '<');
    $payments = $query->execute();
    if (empty($payments)) {
      return;
    }
    $payments = $this->paymentStorage->loadMultiple($payments);
    $this->paymentStorage->delete($payments);
  }

  /**
   * {@inheritdoc}
   */
  public function processPendingAuthorizations() {
    $opp_gateways = $this->getOppGatewayIds(FALSE);
    if (empty($opp_gateways)) {
      return;
    }
    $query = $this->paymentStorage->getQuery();
    $query->condition('payment_gateway', $opp_gateways, 'IN');
    $query->condition('state', 'authorization');
    $query->condition('expires', $this->time->getRequestTime(), '>=');
    $payments = $query->execute();
    if (empty($payments)) {
      return;
    }
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface[] $payments */
    $payments = $this->paymentStorage->loadMultiple($payments);
    foreach ($payments as $payment) {
      /** @var \Drupal\commerce_opp\Plugin\Commerce\PaymentGateway\CopyAndPayInterface $gateway */
      $gateway = $payment->getPaymentGateway()->getPlugin();
      try {
        $payment_status = $gateway->getTransactionStatus($payment);
        if ($payment_status->isAsyncPayment() && $payment_status instanceof Pending) {
          \Drupal::logger('commerce_opp')->info(sprintf('Skip processing of payment ID %s, as it is async type and in pending state.', $payment->id()));
          continue;
        }

        // The ID we receive in this response is different to the checkout ID.
        // The checkout ID was only a temporary remote value, in order to be
        // able to fetch the payment in this callback. Now, we have received the
        // real remote ID and will use it.
        $payment->setRemoteId($payment_status->getId());
        $payment->setRemoteState($payment_status->getCode());
        if ($payment_status instanceof SuccessOrPending) {
          $capture_transition = $payment->getState()->getWorkflow()->getTransition('capture');
          $payment->getState()->applyTransition($capture_transition);
          $payment->save();
        }
        elseif ($payment_status instanceof Rejected) {
          $void_transition = $payment->getState()->getWorkflow()->getTransition('void');
          $payment->getState()->applyTransition($void_transition);
          $payment->save();
        }
      }
      catch (\Exception $ex) {
        \Drupal::logger('commerce_opp')->error($ex->getMessage());
      }
    }
  }

}

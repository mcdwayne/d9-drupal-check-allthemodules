<?php

namespace Drupal\cg_payment\Controller;

use Creditguard\CgRedirectReturn;
use Drupal\cg_payment\Manager\RequestManager;
use Drupal\cg_payment\TransactionInterface;
use Drupal\cg_payment\Utility\TransactionTrait;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class PaymentCompletePage.
 *
 * @package Drupal\cg_payment\Controller
 */
class PaymentCompletePage extends ControllerBase {

  use TransactionTrait;

  /**
   * The request manager.
   *
   * @var \Drupal\cg_payment\Manager\RequestManager
   */
  protected $cgRequestManager;

  /**
   * The RequestManager logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * PaymentCompletePage constructor.
   *
   * @param \Drupal\cg_payment\Manager\RequestManager $cg_request_manager
   *   The RequestManager object.
   * @param \Drupal\cg_payment\Controller\LoggerChannelFactoryInterface $channel_factory
   *   The LoggerChannelFactoryInterface object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The RequestStack object.
   */
  public function __construct(RequestManager $cg_request_manager,
      LoggerChannelFactoryInterface $channel_factory,
      RequestStack $request_stack) {
    $this->cgRequestManager = $cg_request_manager;
    $this->logger = $channel_factory->get('cg_payment');
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cg_payment.cg_request_manager'),
      $container->get('logger.factory'),
      $container->get('request_stack')
    );
  }

  /**
   * Verify that the user has access to the payment complete page.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   The access result.
   */
  public function access() {
    $query_params = $this->request->query;

    $cg_redirect_return = new CgRedirectReturn($query_params);

    // Check if the query params are valid, otherwise throw 403.
    $validation_errors = $cg_redirect_return->getValidationErrors();
    if (!empty($validation_errors)) {
      $this->logger->notice(
        'Invalid parameters received on CG payment complete page, possible hacking attempt. Messages: %messages', [
          '%messages' => implode(', ', $validation_errors),
        ]);
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

  /**
   * The payment complete page where users finds themselves after payment.
   *
   * @return array
   *   The render-able array
   */
  public function page(Request $request) {
    $query_params = $request->query;

    $cg_redirect_return = new CgRedirectReturn($query_params);

    $txnId = $cg_redirect_return->getTxId();
    $shva_code = $cg_redirect_return->getErrorCode();
    $transaction = $this->getTransactionByRemoteId($txnId, [
      'pending',
      'success',
      'failure',
    ]);

    // In case no entities found - return 404 (may be a fraud or an error)
    if (empty($transaction)) {
      throw new NotFoundHttpException();
    }

    // Check the loaded transaction status, if it is other than pending 0 that
    // means that the user probably refreshed the page.
    $transaction_status = $transaction->get('status')->value;
    if ($transaction_status != 'pending') {
      // Show the right page content according to the existing transaction.
      return $this->getPageContent($transaction, ($transaction_status == 'success'));
    }

    // Set the transaction data (without changing the status)
    $transaction->set('shva_code', $shva_code)
      ->set('last_digits', $cg_redirect_return->getLastDigits())
      ->set('expiration_date', $cg_redirect_return->getCardExp())
      ->save();

    if ($cg_redirect_return->isSuccessCode()) {
      // Log successful transactions to log.
      $this->logger->info(
        'Token request completed successfully: TxID: @txnid, internal transaction ID: @tid', [
          '@txnid' => $txnId,
          '@tid' => $transaction->id(),
        ]);

      // Make the actual charge request now that we have the token.
      $charge_response = $this->cgRequestManager->requestChargeToken(
        $txnId,
        $cg_redirect_return->getCardToken(),
        $cg_redirect_return->getCardExp()
      );
    }
    else {
      // Failure - Error.
      $this->logger->warning(
        'Token request failed: TxID: @txnid, Shva Code: @errorCode, Error Text (from CG): @errorText', [
          '@txnid' => $txnId,
          '@errorCode' => $shva_code,
          '@errorText' => $cg_redirect_return->getErrorText(),
        ]);

      $transaction->set('status', 'failure')->save();
    }

    // Return the page's render-able array.
    return $this->getPageContent($transaction, !empty($charge_response));
  }

  /**
   * Method to return the page content.
   *
   * @param \Drupal\cg_payment\TransactionInterface $transaction
   *   The transaction object.
   * @param bool $is_success
   *   True for successful transaction, false otherwise.
   *
   * @return array
   *   Render-able array.
   */
  protected function getPageContent(TransactionInterface $transaction, $is_success) {
    return [
      '#theme' => 'payment_complete',
      '#attributes' => [],
      '#is_success' => $is_success,
      '#transaction' => $transaction,
      '#cg_redirect_return' => new CgRedirectReturn($this->request->query),
    ];
  }

}

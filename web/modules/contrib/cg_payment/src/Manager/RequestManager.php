<?php

namespace Drupal\cg_payment\Manager;

use Creditguard\CgCommandRequestChargeToken;
use Creditguard\CgCommandRequestPaymentFormUrl;
use Drupal\cg_payment\CgChargeEvent;
use Drupal\cg_payment\Entity\Transaction;
use Drupal\cg_payment\RequestInterface;
use Drupal\cg_payment\Utility\TransactionTrait;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Class RequestManager.
 *
 * @package Drupal\cg_payment\Manager
 */
class RequestManager implements RequestInterface {

  use StringTranslationTrait;
  use TransactionTrait;

  protected $config;
  protected $successUrl;
  protected $cancelUrl;
  protected $errorUrl;
  protected $transaction;

  /**
   * The PrivateTempStoreFactory object.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStore;

  /**
   * The EventDispatcherInterface object.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The MessengerInterface object.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

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
   * RequestManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The ConfigFactory object.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $privateTempStore
   *   The PrivateTempStoreFactory object.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The EventDispatcherInterface object.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Messenger object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $channelFactory
   *   The LoggerChannelFactoryInterface object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(ConfigFactory $config,
      PrivateTempStoreFactory $privateTempStore,
      EventDispatcherInterface $dispatcher,
      MessengerInterface $messenger,
      LoggerChannelFactoryInterface $channelFactory,
      RequestStack $request_stack) {
    $this->config = $config;
    $this->privateTempStore = $privateTempStore;
    $this->dispatcher = $dispatcher;
    $this->messenger = $messenger;
    $this->logger = $channelFactory->get('cg_payment');
    $this->request = $request_stack->getCurrentRequest();

    $base_url = $this->request->getSchemeAndHttpHost();

    // Url to redirect user after successful payment.
    $this->successUrl = "${base_url}/payment-complete";
    // Url to redirect user in case he cancel.
    $this->cancelUrl = "${base_url}/payment-complete";
    // Url to redirect user in case of payment failure.
    $this->errorUrl = "${base_url}/payment-complete";
  }

  /**
   * {@inheritdoc}
   */
  public function requestChargeToken($txId, $token, $cardExp, $terminalNumber = NULL, $mid = NULL, $amount = NULL) {
    $transaction = $this->getTransactionByRemoteId($txId);

    // Quit if no transaction can be found with that txId.
    if (empty($transaction)) {
      return FALSE;
    }

    // Get credentials from our configuration.
    // @todo Move config to another service.
    $cg_config = $this->config->get('cg_payment.settings');
    $relayUrl = $cg_config->get('endpoint_url');
    $username = $cg_config->get('user_name');
    $password = $cg_config->get('password');

    // Try getting the charge data from our session storage (saved on the
    // requestPaymentFormUrl method) in case the function haven't receive
    // those details.
    $tempstore = $this->privateTempStore->get('cg_payment');
    $terminalNumber = !empty($terminalNumber) ? $terminalNumber : $tempstore->get('terminal_number');
    $amount = !empty($amount) ? $amount : $tempstore->get('amount');
    $mid = !empty($mid) ? $mid : $tempstore->get('mid');

    if (empty($terminalNumber) || empty($mid) || empty($amount)) {
      // Log the bad call to watchdog.
      $this->logger->notice(
        'Trying to charge token, but some data is missing: TxID: @txnid, terminal id: @terminal_id, mid: @mid, amount: @amount', [
          '@txnid' => $txId,
          '@terminal_id' => $terminalNumber ?: '',
          '@mid' => $mid ?: '',
          '@amount' => $amount ?: '',
        ]);

      return FALSE;
    }

    try {
      $request = new CgCommandRequestChargeToken($relayUrl, $username, $password, $terminalNumber, $mid);
      $request
        ->setTotal($amount)
        ->setCardToken($token)
        ->setCardExp($cardExp)
        ->setTxId($txId);

      // Dispatch event to allow other modules interact with the data before
      // making the actual request to CG (e.g. for adding extra data).
      $event = new CgChargeEvent($request, $transaction);
      $this->dispatcher->dispatch(CgChargeEvent::PRE_CHARGE, $event);

      $charge_response = $request->execute();
      $is_success = $charge_response->isSuccessCode();

      $authNumber = !empty($charge_response->getResponse()->doDeal->authNumber) ? reset($charge_response->getResponse()->doDeal->authNumber) : '-1';
      $transaction->set('expiration_date', $cardExp)
        ->set('confirm_num', $authNumber)
        ->set('status', $is_success ? 'success' : 'failure')
        ->save();

      $this->logger->info(
        'CG charge token request completed: TxID: @txnid, internal transaction ID: @tid', [
          '@txnid' => $txId,
          '@tid' => $transaction->id(),
        ]);

      return $is_success ? $transaction : FALSE;
    }
    catch (\Exception $e) {
      $this->logger->error(
        'CG charge token request failed: TxID: @txnid, error code: @code, error message: @message', [
          '@txnid' => $txId,
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ]);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function requestPaymentFormUrl($terminalNumber, $mid, $amount, $email, $description) {
    $url = NULL;

    $this->transaction = Transaction::create();

    // Get credentials from our configuration.
    $cg_config = $this->config->get('cg_payment.settings');
    $relayUrl = $cg_config->get('endpoint_url');
    $username = $cg_config->get('user_name');
    $password = $cg_config->get('password');

    try {
      $request = new CgCommandRequestPaymentFormUrl($relayUrl, $username, $password, $terminalNumber, $mid);
      $request
        ->setTotal($amount)
        ->setEmail($email)
        ->setDescription($description)
        ->setSuccessUrl($this->successUrl)
        ->setCancelUrl($this->cancelUrl)
        ->setErrorUrl($this->errorUrl);

      $cg_response = $request->execute();

      $redirect_url = $cg_response->getPaymentFormUrl();
      $token_id = $cg_response->getPaymentFormToken();

      $this->transaction
        ->set('status', 'pending')
        ->set('remote_id', $token_id);

      $url = $redirect_url;
    }
    catch (\Exception $e) {
      $this->transaction
        ->set('status', 'failure');
      $this->messenger->addError(
        $this->t('An error as occurred, please try again.')
      );
      print $e->getMessage();
    }

    $this->transaction->save();

    // Save data for later usage (e.g. the actual token charge).
    $tempstore = $this->privateTempStore->get('cg_payment');
    $tempstore->set('amount', $amount);
    $tempstore->set('terminal_number', $terminalNumber);
    $tempstore->set('mid', $mid);

    return $url;
  }

  /**
   * Set the success URL.
   *
   * @param string $successUrl
   *   The success URL.
   *
   * @return \Drupal\cg_payment\Manager\RequestManager
   *   The request manager object.
   */
  public function setSuccessUrl($successUrl) {
    $this->successUrl = $successUrl;
    return $this;
  }

  /**
   * Set the cancel URL.
   *
   * @param string $cancelUrl
   *   The cancel URL.
   *
   * @return \Drupal\cg_payment\Manager\RequestManager
   *   The request manager object.
   */
  public function setCancelUrl($cancelUrl) {
    $this->cancelUrl = $cancelUrl;
    return $this;
  }

  /**
   * Set the error URL.
   *
   * @param string $errorUrl
   *   The error URL.
   *
   * @return \Drupal\cg_payment\Manager\RequestManager
   *   The request manager object.
   */
  public function setErrorUrl($errorUrl) {
    $this->errorUrl = $errorUrl;
    return $this;
  }

  /**
   * Get the transaction object.
   *
   * @return \Drupal\cg_payment\TransactionInterface
   *   The transaction entity.
   */
  public function getTransaction() {
    return $this->transaction;
  }

}

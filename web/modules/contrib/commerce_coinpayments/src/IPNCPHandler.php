<?php

namespace Drupal\commerce_coinpayments;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\commerce_order\Entity\Order;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class IPNCPHandler implements IPNCPHandlerInterface {

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The config object for 'commerce_payment.commerce_payment_gateway.coin_payments'.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $commerceCoinpayments;

  /**
   * Constructs a new PaymentGatewayBase object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \GuzzleHttp\ClientInterface $client
   *   The client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config object.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, ClientInterface $client, ConfigFactoryInterface $configFactory) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->httpClient = $client;
    $this->commerceCoinpayments = $configFactory->get('commerce_payment.commerce_payment_gateway.coin_payments');
  }

  /**
   * {@inheritdoc}
   */
  public function process(Request $request) {
    // Get IPN request data.
    $ipn_data = $this->getRequestDataArray($request->getContent());

    // Exit now if the $_POST was empty.
    if (empty($ipn_data)) {
      $this->logger->warning('IPN URL accessed with no POST data submitted.');
      throw new BadRequestHttpException('IPN URL accessed with no POST data submitted.');
    }

    // If the payment method specifies full IPN logging, do it now.
    if ($this->commerceCoinpayments->get('configuration')['ipn_logging'] == 'yes') {
      $this->logger->notice('Attempting to process IPN @txn_id. @ipn_log', ['@txn_id' => $ipn_data['txn_id'], '@ipn_log' => SafeMarkup::checkPlain(print_r($ipn_data, TRUE))]);
    }

    // Check the post data sent is valid or not.
    if (!$this->commerce_coinpayments_is_ipn_valid($ipn_data)) {
      return FALSE;
    }

    // Check the ipn_type is button type or not.
    if (!isset($ipn_data['ipn_type']) || $ipn_data['ipn_type'] != 'button') {
      $this->logger->notice('Transaction ID @txn_id is not button type, ignored.', ['@txn_id' => $ipn_data['txn_id']]);
      return FALSE;
    }

    // Exit if the IPN has already been processed with the message.
    $transaction = FALSE;
    if (!empty($ipn_data['txn_id']) && $prior_ipn = $this->commerce_coinpayments_ipn_load($ipn_data['txn_id'])) {
      if ($prior_ipn['status'] >= 100 || $prior_ipn['status'] < 0) {
        $this->logger->notice('IPN has already been processed with transaction ID @txn_id, ignored.', ['@txn_id' => $ipn_data['txn_id']]);
        return FALSE;
      }
      $ipn_data['ipn_id'] = $prior_ipn['ipn_id'];
      // Load the prior IPN's transaction and update that with the capture values.
      /** @var \Drupal\commerce_payment\PaymentStorage $storage */
      $storage = $this->entityTypeManager->getStorage('commerce_payment');
      $transaction_array = $storage->loadByProperties(['remote_id' => $prior_ipn['txn_id']]);
      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $transaction */
      $transaction = array_shift($transaction_array);
    }

    // Load the order based on the IPN's invoice number.
    if (!empty($ipn_data['invoice']) && strpos($ipn_data['invoice'], '-') !== FALSE) {
      list($ipn_data['order_id'], $timestamp) = explode('-', $ipn_data['invoice']);
    } elseif (!empty($ipn_data['invoice'])) {
      $ipn_data['order_id'] = $ipn_data['invoice'];
    } else {
      $ipn_data['order_id'] = 0;
      $timestamp = 0;
    }

    // Load the order object using order_id.
    if (!empty($ipn_data['order_id'])) {
      /** @var \Drupal\commerce_order\Entity\Order $order */
      $order = $this->commerce_coinpayments_order_load($ipn_data['order_id']);
    } else {
      $order = FALSE;
    }

    if ($order != FALSE) {
      if ($transaction == FALSE) {
        // Create a new payment transaction for the order.
        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $transaction */
        $transaction = $payment_storage->create([
          'state' => 'new',
          'amount' => $order->getTotalPrice(),
          'payment_gateway' => $order->get('payment_gateway')->getString(),
          'order_id' => $order->id(),
          'remote_id' => $ipn_data['txn_id'],
        ]);
        $transaction->setState('new');
      }

      if ($ipn_data['status'] < 0) {
        $transaction->setRemoteState($ipn_data['status']);
        $transaction->setState('failed');
        $order_state = $order->getState();
        $order_state_transitions = $order_state->getTransitions();
        $order_state->applyTransition($order_state_transitions['cancel']);
        $order->save();
      } else if ($ipn_data['status'] == 100) {
        $transaction->setRemoteState($ipn_data['status']);
        $transaction->setState('completed');
        $transition = $order->getState()->getWorkflow()->getTransition('place');
        $order->getState()->applyTransition($transition);
        $order->save();
      } else {
        $transaction->setRemoteState($ipn_data['status']);
        $transaction->setState('authorization');
      }

      // Save the transaction information.
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      $payment = $payment_storage->save($transaction);

      $ipn_data['transaction_id'] = $transaction->id();
      // Save the processed IPN details.
      $this->commerce_coinpayments_ipn_save($ipn_data);

      $this->logger->info('IPN processed OK for Order @order_number with ID @txn_id.', ['@txn_id' => $ipn_data['txn_id'], '@order_number' => $order->id()]);
      return TRUE;
    } else {
      $this->logger->notice('Could not find order for TxnID: @txn_id, ignored.', ['@txn_id' => $ipn_data['txn_id']]);
      return FALSE;
    }

  }

  /**
   * Check the validation for IPN data.
   *
   * @param array $ipn_data
   *   The IPN request data from coinpayments.
   *
   * @return string
   *   The IPN validation URL.
   */
  protected function commerce_coinpayments_is_ipn_valid($ipn_data) {

    if (!isset($ipn_data['ipn_mode'])) {
      $this->logger->alert('IPN received with no ipn_mode.');
      throw new BadRequestHttpException('IPN received with no ipn_mode.');
      return FALSE;
    }
    if ($ipn_data['ipn_mode'] == 'hmac') {
      if (!isset($_SERVER['HTTP_HMAC']) || empty($_SERVER['HTTP_HMAC'])) {
        $this->logger->alert('No HMAC signature sent.');
        throw new BadRequestHttpException('No HMAC signature sent.');
        return FALSE;
      }

      $request = file_get_contents('php://input');
      if ($request === FALSE || empty($request)) {
        $this->logger->alert('Error reading POST data: @post_data', ['@post_data' => print_r($_SERVER, TRUE).'/'.print_r($_POST, TRUE)]);
        throw new BadRequestHttpException('Error reading POST data:');
        return FALSE;
      }

      $merchant = isset($ipn_data['merchant']) ? $ipn_data['merchant']:'';
      if (empty($merchant)) {
        $this->logger->alert('No Merchant ID passed.');
        throw new BadRequestHttpException('No Merchant ID passed.');
        return FALSE;
      }
      if ($merchant != trim($this->commerceCoinpayments->get('configuration')['merchant_id'])) {
        $this->logger->alert('Invalid Merchant ID.');
        throw new BadRequestHttpException('Invalid Merchant ID.');
        return FALSE;
      }

      $hmac = hash_hmac("sha512", $request, trim($this->commerceCoinpayments->get('configuration')['ipn_secret']));
      if ($hmac != $_SERVER['HTTP_HMAC']) {
        $this->logger->alert('HMAC signature does not match.');
        throw new BadRequestHttpException('HMAC signature does not match.');
        return FALSE;
      }

      return TRUE;
    } else if ($ipn_data['ipn_mode'] == 'httpauth') {
      if (isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] == trim($this->commerceCoinpayments->get('configuration')['merchant_id'])) {
        if (isset($_SERVER['PHP_AUTH_PW']) && $_SERVER['PHP_AUTH_PW'] == trim($this->commerceCoinpayments->get('configuration')['ipn_secret'])) {
          return TRUE;
        } else {
          $this->logger->alert('IPN Secret not correct or no HTTP Auth variables passed. If you are using PHP in CGI mode try the HMAC method.');
          throw new BadRequestHttpException('IPN Secret not correct or no HTTP Auth variables passed. If you are using PHP in CGI mode try the HMAC method.');
        }
      } else {
        $this->logger->alert('Merchant ID not correct or no HTTP Auth variables passed. If you are using PHP in CGI mode try the HMAC method.');
        throw new BadRequestHttpException('Merchant ID not correct or no HTTP Auth variables passed. If you are using PHP in CGI mode try the HMAC method.');
      }
    } else {
      $this->logger->alert('Unknown ipn_mode.');
      throw new BadRequestHttpException('Unknown ipn_mode.');
    }
    return FALSE;
  }

  /**
   * Loads a stored IPN by ID.
   *
   * @param $id
   *   The ID of the IPN to load.
   * @param $type
   *   The type of ID you've specified, either the serial numeric ipn_id or the
   *     actual CoinPayments txn_id. Defaults to txn_id.
   *
   * @return
   *   The original IPN with some meta data related to local processing.
   */
  protected function commerce_coinpayments_ipn_load($id, $type = 'txn_id') {

    $query = $this->connection->select('commerce_coinpayments_ipn', 'cpi');
    $query->fields('cpi');
    $query->condition('cpi.' . $type, $id);
    $result = $query->execute()->fetchAssoc();
    return $result;
  }

  /**
   * Saves an IPN with some meta data related to local processing.
   *
   * @param $ipn
   *   An IPN array with additional parameters for the order_id and Commerce
   *     Payment transaction_id associated with the IPN.
   *
   * @return $result
   *   The operation performed by drupal_write_record() on save; since the IPN is
   *     received by reference, it will also contain the serial numeric ipn_id
   *     used locally.
   */
  protected function commerce_coinpayments_ipn_save(&$ipn_data) {
    if (isset($ipn_data['ipn_id']) && $ipn_data['ipn_id'] > 0 && $this->commerce_coinpayments_ipn_load($ipn_data['txn_id'])) {
      $ipn_data['changed'] = \Drupal::time()->getRequestTime();
      $result = $this->connection->merge('commerce_coinpayments_ipn')
                  ->key(['txn_id' => $ipn_data['txn_id']])
                  ->updateFields([
                    'status' => $ipn_data['status'],
                    'status_text' => $ipn_data['status_text'],
                    'changed' => $ipn_data['changed'],
                  ])->execute();
      return $result;
    } else {
      $ipn_data['created'] = \Drupal::time()->getRequestTime();
      $ipn_data['changed'] = \Drupal::time()->getRequestTime();
      $result = $this->connection->insert('commerce_coinpayments_ipn')
                  ->fields([ 'txn_id', 'ipn_type', 'merchant', 'email', 'order_id',
                    'transaction_id', 'amount1', 'currency1', 'amount2', 'currency2',
                    'status', 'status_text', 'created', 'changed'
                  ])
                  ->values([
                    $ipn_data['txn_id'], $ipn_data['ipn_type'], $ipn_data['merchant'],
                    $ipn_data['email'], $ipn_data['order_id'], $ipn_data['transaction_id'],
                    $ipn_data['amount1'], $ipn_data['currency1'], $ipn_data['amount2'],
                    $ipn_data['currency2'], $ipn_data['status'], $ipn_data['status_text'],
                    $ipn_data['created'], $ipn_data['changed'],
                  ])
                  ->execute();
      return $result;
    }
  }

  /**
   * Deletes a stored IPN by ID.
   *
   * @param $id
   *   The ID of the IPN to delete.
   * @param $type
   *   The type of ID you've specified, either the serial numeric ipn_id or the
   *     actual CoinPayments txn_id. Defaults to txn_id.
   */
  protected function commerce_coinpayments_ipn_delete($id, $type = 'txn_id') {

    $query = $this->connection->delete('commerce_coinpayments_ipn');
    $query->condition($type, $id);
    $query->execute();
  }

  /**
   * Loads the commerce order.
   *
   * @param $order_id
   *   The order ID.
   *
   * @return object
   *   The commerce order object.
   */
  protected function commerce_coinpayments_order_load($order_id) {
    $order = Order::load($order_id);
    return $order ? $order : FALSE;
  }

  /**
   * Get data array from a request content.
   *
   * @param string $request_content
   *   The Request content.
   *
   * @return array
   *   The request data array.
   */
  protected function getRequestDataArray($request_content) {
    parse_str(html_entity_decode($request_content), $ipn_data);
    return $ipn_data;
  }

}

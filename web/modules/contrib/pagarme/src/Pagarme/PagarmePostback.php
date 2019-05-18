<?php

namespace Drupal\pagarme\Pagarme;

use Drupal\pagarme\Pagarme\PagarmeSdk;

/**
 * Class PagarmePostback.
 *
 * @package Drupal\pagarme\Controller
 */
class PagarmePostback {

  private $pagarme_data;

  /**
   * Entity storage for order entities.
   *
   * @var \Drupal\commerce_order\Entity\Order
   */
  private $order;

  private $postback;

  /**
   * PagarmePostback constructor.
   *
   * @param array $pagarme_data
   *   Data from Pagar.me
   */
  public function __construct($pagarme_data, $order, $postback = NULL) {
    $this->pagarme_data = $pagarme_data;
    $this->order = $order;
    $this->postback = $postback;
  }

  /**
   * 
   * @param type $data $_POST data or array based on it to process a Pagar.me POSTback
   * @return \PagarmePostback
   * @throws Exception
   */
  static public function createData($post) {
    if (empty($post['id'])) {
      throw new \Exception(t('The parameters received are invalid.'));
    }
    $postback = self::getPostbackByRemoteId($post['id']);
    if (empty($postback)) {
      throw new \Exception(t('Unable to retrieve postback for the remote id informed.'));
    }
  
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = \Drupal::entityManager()
      ->getStorage('commerce_order')
      ->load($postback['order_id']);
    if (!$order) {
      throw new \Exception(t('Cannot retrieve order for order id.'));
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $payment_gateway */
    $payment_gateway = $order->get('payment_gateway');
    $payment_gateway  = reset($payment_gateway->referencedEntities());
    $plugin_config = $payment_gateway->get('configuration');

    if ($plugin_config['pagarme_debug']) {
      \Drupal::logger('pagarme')->debug('[postbackNotification] <pre>@pre</pre>', array(
        '@pre' => print_r($post, TRUE)));
    }

    // Validating the POSTback source, that is, if it was actually sent by Pagar.me.me
    if (!self::validateRequestSignature($plugin_config)) {
      $message = t('An attempt was made to access the POSTback notification URL from an unknown source. See below: <pre>@pre</pre>', array('@pre' => print_r($_REQUEST, TRUE)));
      \Drupal::logger('pagarme')->error($message);
      throw new \Exception(t('An attempt was made to access the POSTback notification URL from an unknown source'));
    }
    $pagarme_data = (array) $post;
    $pagarme_data['pagarme_id'] = $post['id'];
    $pagarme_data['payment_status'] = $post['current_status'];
    return new PagarmePostback($pagarme_data, $order, $postback);
  }

  /**
   * Main controller for processing Pagar.me POSTback data, ensuring database integrity 
   * with a db_transaction and making it easy to extend with another class, in 
   * projects with special use cases
   * @throws Exception
   */
  public function processPagarmeData() {

    // $transaction = db_transaction();

    try {
      /** @var \Drupal\commerce_payment\Entity\PaymentGateway $payment_gateway */
      $payment_gateway = $this->order->get('payment_gateway');
      $payment_gateway  = current($payment_gateway->referencedEntities());
      $plugin_config = $payment_gateway->get('configuration');

      if ($plugin_config['pagarme_debug']) {
        \Drupal::logger('pagarme')->debug('[processPagarmeData] <pre>@pre</pre>', array(
          '@pre' => print_r($this->pagarme_data, TRUE)));
      }
      $this->save();
    }
    catch (Exception $e) {
      // $transaction->rollback();
      watchdog_exception('pagarme', $e);
      throw $e;
    }
  }

    /**
   * Persist data from Pagar.me POSTback to the database
   * @return boolean
   */
  public function save() {
    $request_time = \Drupal::time()->getRequestTime();

    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    $payment = $this->loadPaymentByRemoteId($this->pagarme_data['pagarme_id']);

    if (!empty($this->postback['ppid'])) {
      $fields = array(
        'payment_status' => $this->pagarme_data['payment_status'],
        'changed' => $request_time
      );
      $payment->state = $fields['payment_status'];
      $payment->setRemoteState($fields['payment_status']);
      $payment->save();
      return \Drupal::database()->update('pagarme_postback')
        ->fields($fields)
        ->condition('ppid', $this->postback['ppid'])
        ->execute();
    }
    else {
      $fields = array(
        'amount' => $this->pagarme_data['amount'],
        'pagarme_id' => $this->pagarme_data['pagarme_id'],
        'payment_status' => $this->pagarme_data['payment_status'],
        'payment_method' => $this->pagarme_data['payment_method'],
        'consumer_email' => $this->pagarme_data['consumer_email'],
        'order_id' => $this->pagarme_data['order_id'],
        'payment_id' => $payment->id(),
        'created' => $request_time,
        'changed' => $request_time,
      );
      return \Drupal::database()->insert('pagarme_postback')
        ->fields($fields)
        ->execute();
    }
  }

  /**
   * Pagar.me POSTback data from pagarme_postback table based on the pagarme_id
   */
  public static function getPostbackByRemoteId($pagarme_id) {
    $result = \Drupal::database()->select('pagarme_postback')
        ->fields('pagarme_postback')
        ->condition('pagarme_id', $pagarme_id)
        ->execute()
        ->fetchAssoc();
    return empty($result) ? FALSE : $result;
  }

  /**
   * Loads the payment for a given remote id.
   *
   * @param string $remote_id
   *   The remote id property for a payment.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   *   Payment object.
   *
   * @todo: to be replaced by Commerce core payment storage method
   * @see https://www.drupal.org/node/2856209
   */
  protected function loadPaymentByRemoteId($remote_id) {
    /** @var \Drupal\commerce_payment\PaymentStorage $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage('commerce_payment');
    $payment_by_remote_id = $storage->loadByProperties(['remote_id' => $remote_id]);
    return reset($payment_by_remote_id);
  }

  static private function validateRequestSignature($plugin_config) {
    $headers = getallheaders();
    if (empty($headers['X-Hub-Signature'])) {
      return false;
    }

    // Get payload
    $payload = file_get_contents('php://input');

    $sdk = new PagarmeSdk($plugin_config['pagarme_api_key']);
    return $sdk->pagarme->postback()->validateRequest($payload, $headers['X-Hub-Signature']);
  }
}

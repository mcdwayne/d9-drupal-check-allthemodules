<?php

namespace Drupal\uc_quickpay\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_payment\Plugin\PaymentMethodManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\uc_order\Entity\Order;

/**
 * Returns response for QuickPay Form Payment Method.
 */
class QuickPayCallbackController extends ControllerBase {

  /**
   * The payment method manager.
   *
   * @var \Drupal\uc_payment\Plugin\PaymentMethodManager
   */
  protected $paymentMethodManager;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The error and warnings logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $log;

  /**
   * Constructs a QuickPayFormController.
   *
   * @param \Drupal\uc_payment\Plugin\PaymentMethodManager $payment_method_manager
   *   The payment method.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The logger.
   */
  public function __construct(PaymentMethodManager $payment_method_manager, SessionInterface $session, LoggerChannelFactory $logger) {
    $this->paymentMethodManager = $payment_method_manager;
    $this->session = $session;
    $this->log = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.uc_payment.method'),
      $container->get('session'),
      $container->get('logger.factory')
    );
  }

  /**
   * Quickpay callback request.
   *
   * @todo Handle Callback from QUickPay payment gateway.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   */
  public function quickpayCallback(Request $request) {
    if (!empty($request->server->get('HTTP_QUICKPAY_CHECKSUM_SHA256'))) {
      // Get request body.
      $request_body = $request->getContent();
      // Store callback data.
      $response_data = Json::decode($request_body);
      if (!empty($response_data)) {
        // Load order using callback uc_order_id.
        $order = Order::load($response_data['variables']['uc_order_id']);

        if (empty($response_data['id'])) {
          \Drupal::logger('uc_2checkout')->notice(t('Quickpay form payment callback response doesn&apos;t have payment id. Please contact with the site administrator.'));
          uc_order_comment_save($order->id(), $order->getOwnerId(), $this->t('Quickpay form payment callback response doesn&apos;t have payment id for Order ID : @order_id.',
              [
                '@order_id' => $order->id(),
              ]
            ), 'admin');
          return;
        }
        // Get string length.
        $order_length = Unicode::strlen((string) $order->id());
        $order_id = Unicode::substr($response_data['order_id'], -$order_length);
        // Get private key configuration.
        $plugin = $this->paymentMethodManager->createFromOrder($order);
        $adminconfiguration = $plugin->getConfiguration();
        // Checking checksum.
        $checksum = $this->callbackChecksum($request_body, $adminconfiguration['api']['private_key']);
        if ($checksum === $request->server->get('HTTP_QUICKPAY_CHECKSUM_SHA256')) {
          if ($order_id != $order->id()) {
            $this->log->error('Quickpay form payment callback response order id is not matched with current order id. Please contact with the site administrator.');
            return;
          }

          // Payment is authorized or not.
          if ($response_data['operations'][0]['type'] === 'authorize') {
            $existing_order_id = db_query("SELECT order_id FROM {uc_payment_quickpay_callback} WHERE order_id = :id", [':id' => $order->id()])->fetchField();
            if (empty($existing_order_id)) {
              $payment_id = $response_data['id'];
              $merchant_id = $response_data['merchant_id'];
              $payment_type = $response_data['metadata']['type'];
              $payment_brand = $response_data['metadata']['brand'];
              $payment_amount = $response_data['operations'][0]['amount'];
              $payment_status = $response_data['operations'][0]['type'];
              $payment_email = $response_data['invoice_address']['email'];
              // Callback response enter to the database.
              db_insert('uc_payment_quickpay_callback')
                ->fields([
                  'order_id' => $order_id,
                  'payment_id' => $payment_id,
                  'merchant_id' => $merchant_id,
                  'payment_type' => $payment_type,
                  'payment_brand' => $payment_brand,
                  'payment_amount' => $payment_amount,
                  'payment_status' => $payment_status,
                  'customer_email' => $payment_email,
                  'created_at' => REQUEST_TIME,
                ])
                ->execute();

              // Order comment.
              uc_order_comment_save($order_id, $order->getOwnerId(), $this->t('Quickpay form payment has been successfully authorized with a Payment ID : @payment_id.',
                [
                  '@payment_id' => $payment_id,
                ]
              ), 'admin');
            }
            // Captured payment.
            if (isset($response_data['operations'][1]) && $response_data['operations'][1]['type'] === 'capture') {
              $payment_id = $response_data['id'];
              $payment_status = $response_data['operations'][1]['type'];
              db_update('uc_payment_quickpay_callback')
                ->fields([
                  'payment_status' => $payment_status,
                ])
                ->condition('order_id', $order_id, '=')
                ->condition('payment_id', $payment_id, '=')
                ->execute();

              // Order comment.
              uc_order_comment_save($order_id, $order->getOwnerId(), $this->t('Quickpay form payment has been successful captured with Payment ID : @payment_id.',
                [
                  '@payment_id' => $payment_id,
                ]
              ), 'admin');
            }
          }
          else {
            // Order comment.
            uc_order_comment_save($order->id(), 1, $this->t("Quickpay form payment is not authorized. You need to contact the site administrator."), 'admin');
          }
        }
      }
      else {
        // Order comment.
        uc_order_comment_save($order->id(), 1, $this->t('Quickpay form payment server is not responded. You need to contact with site administrator.'));
      }
      die('ok');
    }
  }

  /**
   * Checksum function.
   *
   * @todo Create checksum to compare with response checksum.
   */
  protected function callbackChecksum($base, $private_key) {
    return hash_hmac("sha256", $base, $private_key);
  }

}

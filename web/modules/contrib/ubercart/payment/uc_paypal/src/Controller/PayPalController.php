<?php

namespace Drupal\uc_paypal\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\uc_order\Entity\Order;
use Drupal\uc_payment\Plugin\PaymentMethodManager;
use GuzzleHttp\Exception\TransferException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Returns responses for PayPal routes.
 */
class PayPalController extends ControllerBase implements ContainerInjectionInterface {

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
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $dateTime;

  /**
   * Constructs a CheckoutController.
   *
   * @param \Drupal\uc_payment\Plugin\PaymentMethodManager $payment_method_manager
   *   The payment method manager.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param \Drupal\Component\Datetime\TimeInterface $date_time
   *   The datetime.time service.
   */
  public function __construct(PaymentMethodManager $payment_method_manager, SessionInterface $session, TimeInterface $date_time) {
    $this->paymentMethodManager = $payment_method_manager;
    $this->session = $session;
    $this->dateTime = $date_time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.uc_payment.method'),
      $container->get('session'),
      $container->get('datetime.time')
    );
  }

  /**
   * Processes the IPN HTTP request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An empty Response with HTTP status code 200.
   */
  public function ipn(Request $request) {
    $this->processIpn($request->request->all());
    return new Response();
  }

  /**
   * Processes Instant Payment Notifications from PayPal.
   *
   * @param array $ipn
   *   The IPN data.
   */
  protected function processIpn(array $ipn) {
    $amount = $ipn['mc_gross'];
    $email = !empty($ipn['business']) ? $ipn['business'] : $ipn['receiver_email'];
    $txn_id = $ipn['txn_id'];

    if (!isset($ipn['invoice'])) {
      $this->getLogger('uc_paypal')->error('IPN attempted with invalid order ID.');
      return;
    }

    // Extract order and cart IDs.
    $order_id = $ipn['invoice'];
    if (strpos($order_id, '-') > 0) {
      list($order_id, $cart_id) = explode('-', $order_id);
      $this->session->set('uc_cart_id', $cart_id);
    }

    $order = Order::load($order_id);
    if (!$order) {
      $this->getLogger('uc_paypal')->error('IPN attempted for non-existent order @order_id.', ['@order_id' => $order_id]);
      return;
    }

    // @todo Send method name and order ID in the IPN URL?
    $config = $this->paymentMethodManager->createFromOrder($order)->getConfiguration();

    // Optionally log IPN details.
    if (!empty($config['wps_debug_ipn'])) {
      $this->getLogger('uc_paypal')->notice('Receiving IPN at URL for order @order_id. <pre>@debug</pre>', [
        '@order_id' => $order_id,
        '@debug' => print_r($ipn, TRUE),
      ]);
    }

    // Express Checkout IPNs may not have the WPS email stored. But if it is,
    // make sure that the right account is being paid.
    if (!empty($config['wps_email']) && mb_strtolower($email) != mb_strtolower($config['wps_email'])) {
      $this->getLogger('uc_paypal')->error('IPN for a different PayPal account attempted.');
      return;
    }

    // Determine server.
    if (empty($ipn['test_ipn'])) {
      $host = 'https://ipnpb.paypal.com/cgi-bin/webscr';
    }
    else {
      $host = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
    }

    // POST IPN data back to PayPal to validate.
    try {
      $response = \Drupal::httpClient()->request('POST', $host, [
        'form_params' => ['cmd' => '_notify-validate'] + $ipn,
      ]);
    }
    catch (TransferException $e) {
      $this->getLogger('uc_paypal')->error('IPN validation failed with HTTP error %error.', ['%error' => $e->getMessage()]);
      return;
    }

    // Check IPN validation response to determine if the IPN was valid..
    if ($response->getBody() != 'VERIFIED') {
      $this->getLogger('uc_paypal')->error('IPN transaction failed verification.');
      uc_order_comment_save($order_id, 0, $this->t('An IPN transaction failed verification for this order.'), 'admin');
      return;
    }

    // Check for a duplicate transaction ID.
    $duplicate = (bool) db_query_range('SELECT 1 FROM {uc_payment_paypal_ipn} WHERE txn_id = :id AND status <> :status', 0, 1, [':id' => $txn_id, ':status' => 'Pending'])->fetchField();
    if ($duplicate) {
      if ($order->getPaymentMethodId() != 'credit') {
        $this->getLogger('uc_paypal')->notice('IPN transaction ID has been processed before.');
      }
      return;
    }

    db_insert('uc_payment_paypal_ipn')
      ->fields([
        'order_id' => $order_id,
        'txn_id' => $txn_id,
        'txn_type' => $ipn['txn_type'],
        'mc_gross' => $amount,
        'status' => $ipn['payment_status'],
        'receiver_email' => $email,
        'payer_email' => $ipn['payer_email'],
        'received' => $this->dateTime->getRequestTime(),
      ])
      ->execute();

    switch ($ipn['payment_status']) {
      case 'Canceled_Reversal':
        uc_order_comment_save($order_id, 0, $this->t('PayPal has canceled the reversal and returned @amount @currency to your account.', ['@amount' => uc_currency_format($amount, FALSE), '@currency' => $ipn['mc_currency']]), 'admin');
        break;

      case 'Completed':
        if (abs($amount - $order->getTotal()) > 0.01) {
          $this->getLogger('uc_paypal')->warning('Payment @txn_id for order @order_id did not equal the order total.', ['@txn_id' => $txn_id, '@order_id' => $order->id(), 'link' => Link::createFromRoute($this->t('view'), 'entity.uc_order.canonical', ['uc_order' => $order->id()])->toString()]);
        }
        $comment = $this->t('PayPal transaction ID: @txn_id', ['@txn_id' => $txn_id]);
        uc_payment_enter($order_id, 'paypal_wps', $amount, $order->getOwnerId(), NULL, $comment);
        uc_order_comment_save($order_id, 0, $this->t('PayPal IPN reported a payment of @amount @currency.', ['@amount' => uc_currency_format($amount, FALSE), '@currency' => $ipn['mc_currency']]));
        break;

      case 'Denied':
        uc_order_comment_save($order_id, 0, $this->t("You have denied the customer's payment."), 'admin');
        break;

      case 'Expired':
        uc_order_comment_save($order_id, 0, $this->t('The authorization has failed and cannot be captured.'), 'admin');
        break;

      case 'Failed':
        uc_order_comment_save($order_id, 0, $this->t("The customer's attempted payment from a bank account failed."), 'admin');
        break;

      case 'Pending':
        $order->setStatusId('paypal_pending')->save();
        uc_order_comment_save($order_id, 0, $this->t('Payment is pending at PayPal: @reason', ['@reason' => $this->pendingMessage($ipn['pending_reason'])]), 'admin');
        break;

      // You, the merchant, refunded the payment.
      case 'Refunded':
        $comment = $this->t('PayPal transaction ID: @txn_id', ['@txn_id' => $txn_id]);
        uc_payment_enter($order_id, 'paypal_wps', $amount, $order->getOwnerId(), NULL, $comment);
        break;

      case 'Reversed':
        $this->getLogger('uc_paypal')->error('PayPal has reversed a payment!');
        uc_order_comment_save($order_id, 0, $this->t('Payment has been reversed by PayPal: @reason', ['@reason' => $this->reversalMessage($ipn['reason_code'])]), 'admin');
        break;

      case 'Processed':
        uc_order_comment_save($order_id, 0, $this->t('A payment has been accepted.'), 'admin');
        break;

      case 'Voided':
        uc_order_comment_save($order_id, 0, $this->t('The authorization has been voided.'), 'admin');
        break;
    }
  }

  /**
   * Returns a message for the pending reason of a PayPal payment.
   */
  protected function pendingMessage($reason) {
    switch ($reason) {
      case 'address':
        return $this->t('The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set to allow you to manually accept or deny each of these payments.');

      case 'authorization':
        return $this->t('The payment is pending because you set the payment action to Authorization and have not yet captured funds.');

      case 'echeck':
        return $this->t('The payment is pending because it was made by an eCheck that has not yet cleared.');

      case 'intl':
        return $this->t('The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this international payment from your Account Overview.');

      case 'multi_currency':
        return $this->t('The payment is pending because you do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny a payment of this currency from your Account Overview.');

      case 'order':
        return $this->t('The payment is pending because you set the payment action to Order and have not yet captured funds.');

      case 'paymentreview':
        return $this->t('The payment is pending while it is being reviewed by PayPal for risk.');

      case 'unilateral':
        return $this->t('The payment is pending because it was made to an e-mail address that is not yet registered or confirmed.');

      case 'upgrade':
        return $this->t('The payment is pending because it was either made via credit card and you do not have a Business or Premier account or you have reached the monthly limit for transactions on your account.');

      case 'verify':
        return $this->t('The payment is pending because you are not yet a verified PayPal member. Please verify your account.');

      case 'other':
        return $this->t('The payment is pending for a reason other than those listed above. For more information, contact PayPal Customer Service.');

      default:
        return $this->t('Reason "@reason" unknown; contact PayPal Customer Service for more information.', ['@reason' => $reason]);
    }
  }

  /**
   * Returns a message for the reason code of a PayPal reversal.
   */
  protected function reversalMessage($reason) {
    switch ($reason) {
      case 'chargeback':
        return $this->t('The customer has initiated a chargeback.');

      case 'guarantee':
        return $this->t('The customer triggered a money-back guarantee.');

      case 'buyer-complaint':
        return $this->t('The customer filed a complaint about the transaction.');

      case 'refund':
        return $this->t('You gave the customer a refund.');

      default:
        return $this->t('Reason "@reason" unknown; contact PayPal Customer Service for more information.', ['@reason' => $reason]);
    }
  }

}

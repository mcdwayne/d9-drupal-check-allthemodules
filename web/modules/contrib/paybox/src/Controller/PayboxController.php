<?php

namespace Drupal\paybox\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\paybox\CheckSignin;
use Drupal\paybox\PayboxErrors;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class PayboxController.
 *
 * @package Drupal\paybox\Controller
 *
 * A simple controller for paybox.
 */
class PayboxController extends ControllerBase {

  /**
   * The config object for paybox settings.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Current requeststack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Logger object.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerChannelFactory;

  /**
   * CheckSignin object.
   *
   * @var \Drupal\paybox
   */
  protected $checkSignin;

  /**
   * PayboxErrors object.
   *
   * @var \Drupal\paybox
   */
  protected $payboxErrors;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $config,
  RequestStack $requestStack,
        LoggerChannelFactory $loggerChannelFactory,
        CheckSignin $checkSignin,
  PayboxErrors $payboxErrors
    ) {

    $this->config = $config->get('paybox.settings');
    $this->requestStack = $requestStack;
    $this->loggerChannelFactory = $loggerChannelFactory;
    $this->checkSignin = $checkSignin;
    $this->payboxErrors = $payboxErrors;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('config.factory'),
    $container->get('request_stack'),
    $container->get('logger.factory'),
    $container->get('paybox.check_signin'),
    $container->get('paybox.errors')
    );
  }

  /**
   * Checks if the server ip belongs to Paybox.
   */
  public function isAllowed() {
    $allowed_ips = $this->config->get('paybox_authorized_ips');
    $allowed_ips = explode(",", $allowed_ips);

    if (in_array(\Drupal::request()->server->get('REMOTE_ADDR'), $allowed_ips)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * Handles ipn callback from paybox.
   */
  public function ipnCallbackPage() {
    $order_id = $this->requestStack->getCurrentRequest()->query->get('order_id');
    $error = $this->requestStack->getCurrentRequest()->query->get('error');
    if ($this->checkSignin->checkUserSign($this->requestStack->getCurrentRequest()->server->get('QUERY_STRING'))) {
      // Check payment is accepted.
      if ($error === '00000') {
        $status = PAYBOX_PAYMENT_STATUS_SUCCESS;
      }
      else {
        $message = $this->payboxErrors->getErrorMsg($error);
        // Log Paybox signature errors.
        \Drupal::logger('paybox')->error('Error @error: %msg', ['@error' => $error, '%msg' => $message]);

        // Invalidate the transaction.
        $status = PAYBOX_PAYMENT_STATUS_FAILURE;
      }
    }
    else {
      // Log paybox signature errors.
      // @todo: Check if report logs are generated or not.

      $this->loggerChannelFactory->get('paybox')->notice('Paybox System has failed to encrypt his own data for order @order_id', ['@order_id' => $order_id]);

      // Invalidate the transaction.
      $status = PAYBOX_PAYMENT_STATUS_FAILURE;
    }

    // @todo: Check if the module invoke all is implemented fine or not.
    \Drupal::moduleHandler()->invokeAll('paybox_update_status', $args = [$order_id, $status]);

    // Output an empty HTML page.
    return NULL;
  }

  /**
   * Check if signature of the Paybox server's response URL is correct.
   *
   * @return bool
   *   TRUE if signing is correct, FALSE otherwise.
   */
  public function checkSign($query_string) {
    $matches = [];
    if (preg_match('/(?:q=.*?&)?(.*)&sig=(.*)$/', $query_string, $matches)) {
      $data = $matches[1];
      $sig = base64_decode(urldecode($matches[2]));

      $key_file = drupal_get_path('module', 'paybox') . '/pubkey.pem';
      if ($key_file_content = file_get_contents($key_file)) {
        if ($key = openssl_pkey_get_public($key_file_content)) {
          return openssl_verify($data, $sig, $key);
        }
      }
      \Drupal::logger('paybox')->notice('Cannot read Paybox System public key file (@file)', ['@file' => $key_file]);
    }
    return FALSE;
  }

  /**
   * Handles return from paybox.
   */
  public function returnPage() {
    $return_url = $this->requestStack->getCurrentRequest()->query->get('return_url');
    $result = $this->requestStack->getCurrentRequest()->query->get('result');
    switch ($result) {
      case 'validated':
        drupal_set_message(\Drupal::config('paybox.settings')->get('paybox_effectue_message'), 'status');
        break;

      case 'denied':
        drupal_set_message(\Drupal::config('paybox.settings')->get('paybox_refuse_message'), 'error');
        break;

      case 'canceled':
        drupal_set_message(\Drupal::config('paybox.settings')->get('paybox_annule_message'), 'warning');
        break;
    }

    $redirectResponse = new RedirectResponse($return_url);
    $redirectResponse->setTargetUrl($return_url)->send();
  }

}

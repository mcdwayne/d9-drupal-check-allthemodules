<?php

namespace Drupal\commerce_quickpay_gateway\Access;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Core\Routing\Access\AccessInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult;
use Psr\Log\LoggerInterface;

/**
 * Checks access for the payment callback from QuickPay.
 */
class CommerceQuickpayCallbackAccessCheck implements AccessInterface {
  /**
   * Logger interface.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * QuickpayIntegrationCallbackAccessCheck constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger interface.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Access callback to check that the callback hasn't been tampered with.
   *
   * @param Request $request
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(Request $request) {
    $content = json_decode($request->getContent());

    // Make sure the request could be decoded.
    if (json_last_error() !== JSON_ERROR_NONE) {
      $this->logger->error("Couldn't decode request from QuickPay");
      return AccessResult::forbidden();
    }

    // Compare the calculated checksum based on the request with the checksum in the request.
    $checksum_calculated = $this->getChecksumFromRequest($content);
    $checksum_requested = $request->server->get('HTTP_QUICKPAY_CHECKSUM_SHA256');
    if (empty($checksum_requested) || strcmp($checksum_calculated, $checksum_requested) !== 0) {
      $this->logger->error('Computed checksum does not match header checksum.');
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

  /**
   * Build the checksum from the request callback from quickpay.
   *
   * @param object $content
   *
   * @return string
   */
  private function getChecksumFromRequest($content) {
    // Load the payment gateway to find the private key.
    $paymentGateway = PaymentGateway::load($content->variables->payment_gateway);
    if (!$paymentGateway) {
      $this->logger->error("Couldn't load payment information from {$content->variables->payment}");
      return false;
    }

    return hash_hmac('sha256', json_encode($content), $paymentGateway->getPluginConfiguration()['private_key']);
  }

}

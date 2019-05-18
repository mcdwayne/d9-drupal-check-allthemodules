<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna;

/**
 * Value object for authorization response.
 */
class AuthorizationResponse {

  protected $orderId;
  protected $redirectUrl;
  protected $fraudStatus;

  /**
   * Constructsa a new instance.
   *
   * @param string $orderId
   *   The order id.
   * @param string $redirectUrl
   *   The redirect url.
   * @param string $fraudStatus
   *   The fraud status.
   */
  public function __construct(string $orderId, string $redirectUrl, string $fraudStatus) {
    $this->orderId = $orderId;
    $this->redirectUrl = $redirectUrl;
    $this->fraudStatus = $fraudStatus;
  }

  /**
   * Creates new response object for given response.
   *
   * @param array $authorization
   *   The authorization.
   *
   * @return self
   *   The self.
   */
  public static function createFromArray(array $authorization) : self {
    return new static($authorization['order_id'], $authorization['redirect_url'], $authorization['fraud_status']);
  }

  /**
   * Gets the order id.
   *
   * @return string
   *   The order id.
   */
  public function getOrderId() : string {
    return $this->orderId;
  }

  /**
   * Gets the redirect url.
   *
   * @return string
   *   The redirect url.
   */
  public function getRedirectUrl() : string {
    return $this->redirectUrl;
  }

  /**
   * Whether the order is fraudulent.
   *
   * @return bool
   *   The fraudulent status.
   */
  public function isFraud() : bool {
    return $this->fraudStatus === 'REJECTED';
  }

  /**
   * Whether the order is pending.
   *
   * @return bool
   *   The pending status.
   */
  public function isPending() : bool {
    return $this->fraudStatus === 'PENDING';
  }

  /**
   * Whether the order is valid.
   *
   * @return bool
   *   The valid status.
   */
  public function isAccepted() : bool {
    return $this->fraudStatus === 'ACCEPTED';
  }

}

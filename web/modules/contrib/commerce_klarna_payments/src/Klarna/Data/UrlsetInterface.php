<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data;

/**
 * An interface to describe url sets.
 */
interface UrlsetInterface extends ObjectInterface {

  /**
   * URL of merchant confirmation page.
   *
   * @param string $url
   *   The url.
   *
   * @return $this
   *   The self.
   */
  public function setConfirmation(string $url) : UrlsetInterface;

  /**
   * URL for notifications on pending orders.
   *
   * @param string $url
   *   The url.
   *
   * @return $this
   *   The self.
   */
  public function setNotification(string $url) : UrlsetInterface;

  /**
   * URL that will be requested when an order is completed.
   *
   * @param string $url
   *   The url.
   *
   * @return $this
   *   The self.
   */
  public function setPush(string $url) : UrlsetInterface;

}

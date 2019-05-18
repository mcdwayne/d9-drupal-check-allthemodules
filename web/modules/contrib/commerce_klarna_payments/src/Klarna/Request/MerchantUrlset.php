<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Request;

use Drupal\commerce_klarna_payments\Klarna\Data\UrlsetInterface;
use Drupal\commerce_klarna_payments\Klarna\ObjectNormalizer;

/**
 * Value object for merchant URLs.
 */
class MerchantUrlset implements UrlsetInterface {

  use ObjectNormalizer;

  protected $data = [];

  /**
   * Constructs a new instance.
   *
   * @param array $data
   *   The data.
   */
  public function __construct(array $data = []) {
    if (isset($data['confirmation'])) {
      $this->setConfirmation($data['confirmation']);
    }
    if (isset($data['notification'])) {
      $this->setNotification($data['notification']);
    }
    if (isset($data['push'])) {
      $this->setPush($data['push']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setConfirmation(string $url) : UrlsetInterface {
    $this->data['confirmation'] = $url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setNotification(string $url) : UrlsetInterface {
    $this->data['notification'] = $url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPush(string $url) : UrlsetInterface {
    $this->data['push'] = $url;
    return $this;
  }

}

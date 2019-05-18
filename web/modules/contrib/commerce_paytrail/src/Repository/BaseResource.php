<?php

declare(strict_types = 1);

namespace Drupal\commerce_paytrail\Repository;

/**
 * Defines the base resource.
 */
abstract class BaseResource {

  /**
   * The merchant hash.
   *
   * @var string
   */
  protected $merchantHash;

  /**
   * Constructs a new instance.
   *
   * @param string $merchantHash
   *   The merchant hash.
   */
  public function __construct(string $merchantHash) {
    $this->merchantHash = $merchantHash;
  }

  /**
   * {@inheritdoc}
   */
  public function generateAuthCode(array $values) : string {
    return strtoupper(hash('sha256', $this->merchantHash . '|' . implode('|', $values)));
  }

  /**
   * {@inheritdoc}
   */
  public function generateReturnChecksum(array $values) : string {
    return strtoupper(hash('sha256', implode('|', $values) . '|' . $this->merchantHash));
  }

}

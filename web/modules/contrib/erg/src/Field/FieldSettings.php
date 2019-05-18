<?php

declare(strict_types = 1);

namespace Drupal\erg\Field;

use Drupal\erg\EntityReference;
use Drupal\erg\Guard\GuardInterface;

/**
 * Provides ERG field settings.
 */
final class FieldSettings {

  private $guards = [];
  private $weight = 0;

  /**
   * Creates a new instance.
   */
  private function __construct() {
  }

  /**
   * Creates a new instance.
   *
   * @return static
   *   The new instance.
   */
  public static function create(): self {
    return new static();
  }

  /**
   * Creates a new instance with the given guards.
   *
   * @param \Drupal\erg\Guard\GuardInterface[]|iterable $guards
   *   The additional guards.
   *
   * @return static
   *   The new instance.
   */
  public function withGuards(iterable $guards): self {
    foreach ($guards as $guard) {
      assert($guard instanceof GuardInterface);
    }
    $settings = clone $this;
    foreach ($guards as $guard) {
      $settings->guards[] = $guard;
    }
    return $settings;
  }

  /**
   * Gets the guards.
   *
   * @return \Drupal\erg\Guard\GuardInterface[]
   *   The guards.
   */
  public function getGuards(): array {
    return $this->guards;
  }

  /**
   * Creates a new instance with the given weight.
   *
   * @param int $weight
   *   The new weight.
   *
   * @return static
   *   The new instance.
   */
  public function withWeight(int $weight): self {
    $settings = clone $this;
    $settings->weight = $weight;
    return $settings;
  }

  /**
   * Gets the weight.
   *
   * @return int
   *   The weight.
   */
  public function getWeight(): int {
    return $this->weight;
  }

  /**
   * Gets the response type.
   *
   * @return string
   *   One of the \Drupal\erg\EntityReference::RESPONSE_* constants.
   */
  public function getResponse(): string {
    // We currently only support immediate responses.
    return EntityReference::RESPONSE_IMMEDIATE;
  }

}

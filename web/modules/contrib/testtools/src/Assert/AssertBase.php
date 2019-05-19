<?php

declare(strict_types=1);

namespace Drupal\testtools\Assert;

/**
 * Base class to store the name of the assert.
 */
abstract class AssertBase implements AssertInterface {

  /**
   * Assert name.
   *
   * @var string
   */
  private $name;

  /**
   * AssertBase constructor.
   *
   * @param string $name
   *   Assert name.
   */
  public function __construct(string $name) {
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  final public function getName(): string {
    return $this->name;
  }

}

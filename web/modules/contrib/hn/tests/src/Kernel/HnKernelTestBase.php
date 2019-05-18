<?php

namespace Drupal\Tests\hn\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\hn\Traits\UserTrait;

/**
 * Base class for hn Kernel tests.
 */
abstract class HnKernelTestBase extends KernelTestBase {
  use UserTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'hn',
    'serialization',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('system');
    $this->installSchema('system', 'sequences');
    $this->installConfig('hn');
  }

}

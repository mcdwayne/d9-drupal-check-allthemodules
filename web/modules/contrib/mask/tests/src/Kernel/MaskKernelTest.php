<?php

namespace Drupal\Tests\mask\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for Mask Field's kernel tests.
 *
 * @group mask
 */
abstract class MaskKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'mask',
  ];

  /**
   * Editable module settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig([
      'mask',
    ]);

    $this->config = \Drupal::configFactory()->getEditable('mask.settings');
  }

}

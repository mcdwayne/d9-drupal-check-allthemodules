<?php

namespace Drupal\Tests\prometheus_exporter\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Abstract base class for Prometheus Exporter kernel tests.
 */
abstract class PrometheusExporterKernelTestBase extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'prometheus_exporter',
    'prometheus_exporter_test',
    'user',
    'node',
    'update',
    'system',
  ];

  /**
   * Kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['user']);
    $this->installSchema('system', ['sequences', 'key_value_expire']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $this->httpKernel = $this->container->get('http_kernel');

  }

}

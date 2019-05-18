<?php

namespace Drupal\Tests\healthz\Kernel;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for the HealthzCheck plugins.
 *
 * @group healthz
 */
class PluginKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'healthz_test_plugin',
    'healthz',
  ];

  /**
   * Tests plugin crud functionality.
   */
  public function testPluginCrud() {
    $checks = [
      'passing_check' => [
        'id' => 'passing_check',
        'provider' => 'healthz_test_plugin',
        'status' => TRUE,
        'weight' => -10,
        'failure_status_code' => 503,
        'settings' => [],
      ],
    ];
    $collection = new DefaultLazyPluginCollection($this->container->get('plugin.manager.healthz'), $checks);
    /** @var \Drupal\healthz\Plugin\HealthzCheckInterface $plugin */
    $plugin = $collection->get('passing_check');

    $expected = $checks['passing_check'];
    $this->assertEquals($expected, $plugin->getConfiguration());
    $this->assertEquals('Passing check', $plugin->getLabel());
    $this->assertEquals('A passing check', $plugin->getDescription());
    $this->assertEquals(503, $plugin->getFailureStatusCode());
    $this->assertEquals(TRUE, $plugin->getStatus());
    $this->assertEquals(-10, $plugin->getWeight());
  }

}

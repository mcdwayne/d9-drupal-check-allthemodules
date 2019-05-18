<?php

namespace Drupal\Tests\prometheus_exporter\Kernel;

use Symfony\Component\HttpFoundation\Request;

/**
 * Tests for metrics.
 *
 * @group prometheus_exporter
 */
class MetricsEndpointTest extends PrometheusExporterKernelTestBase {

  /**
   * The test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->user = $this->createUser(['access prometheus metrics']);
    $this->setCurrentUser($this->user);
  }

  /**
   * Tests the metrics endpoint.
   */
  public function testMetrics() {
    $request = Request::create('/metrics');
    $response = $this->httpKernel->handle($request)->getContent();

    $this->assertContains('# HELP drupal_test_total A test collector.', $response);
    $this->assertContains('# TYPE drupal_test_total gauge', $response);
    $this->assertContains('drupal_test_total{foo="bar"} 1234', $response);
    $this->assertContains('drupal_test_total{baz="wiz"} 5678', $response);
  }

}

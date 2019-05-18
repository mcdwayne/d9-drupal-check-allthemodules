<?php

namespace Drupal\Tests\prometheus_exporter\Kernel\Form;

use Drupal\Tests\prometheus_exporter\Kernel\PrometheusExporterKernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\prometheus_exporter\Form\PrometheusExporterSettings
 * @group prometheus_exporter
 */
class PrometheusExporterSettingsTest extends PrometheusExporterKernelTestBase {

  /**
   * The user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->createUser(['administer prometheus exporter settings']);
    $this->setCurrentUser($this->user);
  }

  /**
   * Tests the metrics endpoint.
   */
  public function testSettingsForm() {
    $request = Request::create('/admin/config/system/prometheus_exporter');
    $response = $this->httpKernel->handle($request)->getContent();

    $this->assertContains("Prometheus Exporter settings", $response);

    // TODO add form assertions.
  }

}

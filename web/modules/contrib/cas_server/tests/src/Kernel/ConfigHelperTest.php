<?php

namespace Drupal\Tests\cas_server\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\cas_server\Entity\CasServerService;

/**
 * Tests the services definition behavior.
 *
 * @group cas_server
 */
class ConfigHelperTest extends EntityKernelTestBase {

  public static $modules = ['cas_server'];

  protected function setUp() {
    parent::setUp();

    $this->installConfig(['cas_server']);
    $test_service = CasServerService::create([
      'id' => 'test_service',
      'label' => 'Test Service',
      'service' => 'htt*://foo.example.com*',
      'sso' => TRUE,
      'attributes' => [
        'field_test_attributes' => 'field_test_attributes',
        'mail' => 'mail',
      ],
    ]);

    $test_service->save();
    $this->configHelper = $this->container->get('cas_server.config_helper');

  }

  /**
   * Tests the service pattern matching.
   */
  function testPatternMatchSuccess() {
    $match = $this->configHelper->checkServiceAgainstWhitelist("https://foo.example.com/bar?q=baz#quux");

    $this->assertEquals(TRUE, $match);
  }

  /**
   * Tests the service pattern matching.
   */
  function testPatternMatchFailure() {
    $match = $this->configHelper->checkServiceAgainstWhitelist("http://bar.example.com");

    $this->assertEquals(FALSE, $match);
  }


  /**
   * Tests that the correct attributes are returned from a matched service.
   */
  function testGetServiceAttributes() {
    $attributes = $this->configHelper->getAttributesForService('https://foo.example.com');

    $this->assertEquals(['field_test_attributes', 'mail'], $attributes);
  }

}

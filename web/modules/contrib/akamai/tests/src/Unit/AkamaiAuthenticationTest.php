<?php

namespace Drupal\Tests\akamai\Unit;

use Drupal\akamai\AkamaiAuthentication;
use Drupal\akamai\KeyProviderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\akamai\AkamaiAuthentication
 *
 * @group Akamai
 */
class AkamaiAuthenticationTest extends UnitTestCase {

  /**
   * Tests that we can authorise when specifying edgerc file.
   *
   * @covers ::create
   * @covers ::getAuth
   */
  public function testSetupEdgeRc() {
    $config = $this->getEdgeRcConfig();
    $auth = AkamaiAuthentication::create(
      $this->getConfigFactoryStub(['akamai.settings' => $config]),
      $this->prophesize(MessengerInterface::class)->reveal(),
      $this->prophesize(KeyProviderInterface::class)->reveal()
    );
    $expected = [
      'client_token' => 'edgerc-test-client-token',
      'client_secret' => 'edgerc-test-client-secret',
      'access_token' => 'edgerc-test-access-token',
    ];
    $this->assertEquals($expected, $auth->getAuth());
    $this->assertEquals(get_class($auth), 'Drupal\akamai\AkamaiAuthentication');
  }

  /**
   * Returns config for edge rc authentication mode.
   *
   * @return array
   *   An array of config values.
   */
  protected function getEdgeRcConfig() {
    return [
      'storage_method' => 'file',
      'edgerc_path' => realpath(__DIR__ . '/fixtures/.edgerc'),
      'edgerc_section' => 'default',
    ];
  }

}

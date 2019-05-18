<?php

namespace Drupal\Tests\media_acquiadam\unit;

use Drupal\Core\Session\AccountProxy;
use Drupal\media_acquiadam\ClientFactory;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserDataInterface;
use GuzzleHttp\Client as GClient;

/**
 * Client factory test.
 *
 * @group media_acquiadam
 */
class AcquiadamClientFactoryTest extends UnitTestCase {

  /**
   *
   */
  public function testFactory() {
    $config_factory = $this->getConfigFactoryStub([
      'media_acquiadam.settings' => [
        'username' => 'WDusername',
        'password' => 'WDpassword',
        'client_id' => 'WDclient-id',
        'secret' => 'WDsecret',
      ],
    ]);
    $guzzle_client = new GClient();
    $client_factory = new ClientFactory($config_factory, $guzzle_client, $this->getMock(UserDataInterface::class), $this->getMock(AccountProxy::class));

    $client = $client_factory->get('background');

    $this->assertInstanceOf('cweagans\webdam\Client', $client);
  }

}

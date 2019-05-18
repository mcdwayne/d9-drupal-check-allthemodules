<?php

namespace Drupal\Tests\media_acquiadam\unit;

use Drupal\Core\Session\AccountProxy;
use Drupal\media_acquiadam\ClientFactory;
use Drupal\media_acquiadam\Acquiadam;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserDataInterface;
use GuzzleHttp\Client as GClient;

/**
 * Service test.
 *
 * @group media_acquiadam
 */
class AcquiadamServiceTest extends UnitTestCase {

  /**
   * Saves some typing.
   */
  public function getConfigFactoryStub(array $configs = []) {
    return parent::getConfigFactoryStub([
      'media_acquiadam.settings' => [
        'username' => 'WDusername',
        'password' => 'WDpassword',
        'client_id' => 'WDclient-id',
        'secret' => 'WDsecret',
      ],
    ]);
  }

  /**
   *
   */
  public function testConstructor() {
    $client_factory = new ClientFactory($this->getConfigFactoryStub(), new GClient(), $this->getMock(UserDataInterface::class), $this->getMock(AccountProxy::class));
    $acquiadam = new Acquiadam($client_factory, 'background');
    $this->assertInstanceOf('Drupal\media_acquiadam\Acquiadam', $acquiadam);
  }

}

<?php

namespace Drupal\Tests\bynder\Unit;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\bynder\BynderApi;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Component\Datetime\TimeInterface;

/**
 * @coversDefaultClass \Drupal\bynder\BynderApi
 *
 * @group bynder
 */
class BynderApiUnitTest extends UnitTestCase {

  /**
   * @covers ::hasAccessToken
   *
   * @dataProvider providerHasAccessToken
   */
  public function testHasAccessToken($session_data, $valid_hash, $state_times, $expected) {
    $session = $this->prophesize(SessionInterface::class);
    $session->get('bynder', [])->willReturn($session_data)->shouldBeCalledTimes(1);
    $state = $this->prophesize(StateInterface::class);
    $state->get('bynder_config_hash')->willReturn($valid_hash)->shouldBeCalledTimes($state_times);
    $logger = $this->prophesize(LoggerChannelFactoryInterface::class);
    $config = $this->prophesize(ConfigFactoryInterface::class);
    $cache = $this->prophesize(CacheBackendInterface::class);
    $time = $this->prophesize(TimeInterface::class);

    $api = $this->getMock(BynderApi::class, ['__call'], [$config->reveal(), $logger->reveal(), $session->reveal(), $state->reveal(), $cache->reveal(), $time->reveal()]);
    $this->assertEquals($expected, $api->hasAccessToken());
  }

  /**
   * Data provider for testHasAccessToken().
   */
  public function providerHasAccessToken() {
    $data = [];
    $data['no_session_data'] = [
      [],
      'valid_hash',
      0,
      FALSE
    ];
    $data['no_token'] = [
      ['access_token' => ['oauth_token_secret' => 'secret']],
      'valid_hash',
      0,
      FALSE
    ];
    $data['no_secret'] = [
      ['access_token' => ['oauth_token' => 'token']],
      'valid_hash',
      0,
      FALSE
    ];
    $data['no_hash'] = [
      ['access_token' => ['oauth_token' => 'token', 'oauth_token_secret' => 'secret']],
      'valid_hash',
      0,
      FALSE,
    ];
    $data['valid'] = [
      ['access_token' => ['oauth_token' => 'token', 'oauth_token_secret' => 'secret'], 'config_hash' => 'valid_hash'],
      'valid_hash',
      1,
      TRUE,
    ];
    return $data;
  }

}

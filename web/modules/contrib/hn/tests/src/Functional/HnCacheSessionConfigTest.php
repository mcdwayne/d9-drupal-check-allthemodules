<?php

namespace Drupal\Tests\hn\Functional;

/**
 * This tests the combination of hn_cache_session and hn_config.
 *
 * @group hn_cache_session
 */
class HnCacheSessionConfigTest extends HnFunctionalTestBase {

  public static $modules = [
    'hn_cache_session',
    'hn_config',
    'hn_test_menu',
  ];

  /**
   * This tests the response of a node that also returns config.
   *
   * The config should not be there the second time the node is requested. Also
   * not as an empty array.
   *
   * @see https://www.drupal.org/node/2918729
   */
  public function testSessionResponseWithConfig() {
    $response = $this->getHnJsonResponse('/node/1');

    $user = $response['__hn']['request']['user'];
    $token = $response['__hn']['request']['token'];

    $this->assertTrue(isset($response['data']['config__menus']));

    // Do a second response with only the user set, this should return the same
    // data as the first response.
    $secondResponse = $this->getHnJsonResponse('/node/1', ['_hn_user' => $user, '_hn_verify' => $token]);

    $this->assertFalse(isset($secondResponse['data']['config__menus']));
  }

}

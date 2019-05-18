<?php

namespace Drupal\Tests\hn\Functional;

use Drupal\node\Entity\Node;

/**
 * Provides some basic tests with the session cache.
 *
 * @group hn_cache_session
 */
class HnCacheSessionTest extends HnFunctionalTestBase {

  public static $modules = [
    'hn_cache_session',
  ];

  /**
   * The internal node url.
   *
   * @var string
   */
  private $nodeUrl;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $test_node = Node::create([
      'type' => 'hn_test_basic_page',
      'title' => 'Test node',
    ]);

    $test_node->save();

    // We get the internal path to exclude the subdirectory the Drupal is
    // installed in.
    $this->nodeUrl = $test_node->toUrl()->getInternalPath();
  }

  public function testSessionTokens() {
    $response = $this->getHnJsonResponse($this->nodeUrl);

    $this->assertEquals(
      $response['data'][$response['paths'][$this->nodeUrl]]['title'],
      'Test node'
    );

    $user = $response['__hn']['request']['user'];
    $token = $response['__hn']['request']['token'];

    $this->assertTrue(!empty($user));
    $this->assertTrue(!empty($token));

    // Do a second response with only the user set, this should return the same
    // data as the first response.
    $secondResponse = $this->getHnJsonResponse($this->nodeUrl, ['_hn_user' => $user]);

    $this->assertEquals(
      $response['data'],
      $secondResponse['data']
    );

    $this->assertEquals($user, $response['__hn']['request']['user']);

    $responseWithoutData = $this->getHnJsonResponse($this->nodeUrl, [
      '_hn_user' => $user,
      '_hn_verify' => [$token],
    ]);

    $this->assertTrue(!empty($responseWithoutData['data'][$response['paths'][$this->nodeUrl]]));
    $this->assertTrue(empty($responseWithoutData['data'][$response['paths'][$this->nodeUrl]]['title']));
  }

}

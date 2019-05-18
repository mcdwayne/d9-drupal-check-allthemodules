<?php

namespace Drupal\Tests\hn\Functional;

use Drupal\redirect\Entity\Redirect;

/**
 * Provides some basic tests with permissions of the HN module.
 *
 * @group hn
 */
class HnPathsTest extends HnFunctionalTestBase {

  public static $modules = [
    'hn_test',
  ];

  /**
   * Test a path alias.
   */
  public function testPathAlias() {
    /** @var \Drupal\Core\Path\AliasStorageInterface $alias_storage */
    $alias_storage = \Drupal::service('path.alias_storage');
    $alias_storage->save('/node/1', '/test');

    $response = $this->getHnJsonResponse('/test');
    $this->assertFalse(empty($response['paths'][base_path() . 'test']));

    $response = $this->getHnJsonResponse('/node/1');
    $this->assertFalse(empty($response['paths'][base_path() . 'test']));
  }

  /**
   * Test if redirects are handled correctly.
   */
  public function testRedirect() {

    // Set up two redirects:
    // - test-1 ==> node/1
    // - test-2 ==> node/2 ==> node-2 (redirect + alias)
    Redirect::create([
      'redirect_source' => 'test-1',
      'redirect_redirect' => 'internal:/node/1',
      'language' => 'und',
      'status_code' => '301',
    ])->save();

    Redirect::create([
      'redirect_source' => 'test-2',
      'redirect_redirect' => 'internal:/node/2',
      'language' => 'und',
      'status_code' => '301',
    ])->save();

    /** @var \Drupal\Core\Path\AliasStorageInterface $alias_storage */
    $alias_storage = \Drupal::service('path.alias_storage');
    $alias_storage->save('/node/2', '/node-2');

    // Test both redirects, with and without a / in front.
    foreach ([1 => base_path() . 'node/1', 2 => base_path() . 'node-2'] as $testNr => $pathResult) {
      foreach ([TRUE, FALSE] as $withSlashBeforePath) {

        $path = ($withSlashBeforePath ? '/' : '') . 'test-' . $testNr;

        $response = $this->getHnJsonResponse($path);

        $this->assertNotEmpty($response['paths'][$path]);
        $this->assertEquals($response['paths'][$path], $response['paths'][$pathResult]);

        $nodeResponse = $response['data'][$response['paths'][$path]];

        $this->assertEquals($nodeResponse['__hn']['url'], $pathResult);
        $this->assertEquals(301, $nodeResponse['__hn']['status']);
        $this->assertEquals(hn_test_node_base($testNr)['title'], $nodeResponse['title']);

      }
    }
  }

  /**
   * Test the response of a 404 page.
   */
  public function test404() {

    $path = '/this-path-does-not-exist';

    // First, test without setting a 404 page. See issue #2930544.
    $response = $this->getHnResponse($path);
    $this->assertContains('The 404 page can&#039;t be loaded.', $response);

    // Set /node/1 as 404 page.
    \Drupal::configFactory()->getEditable('system.site')->set('page.404', '/node/1')->save();
    $pathResult = base_path() . 'node/1';

    $response = $this->getHnJsonResponse($path);

    $this->assertNotEmpty($response['paths'][$path]);
    $this->assertEquals($response['paths'][$path], $response['paths'][$pathResult]);
    $nodeResponse = $response['data'][$response['paths'][$path]];

    $this->assertEquals($nodeResponse['__hn']['url'], $pathResult);
    $this->assertEquals(404, $nodeResponse['__hn']['status']);
    $this->assertEquals(hn_test_node_base(1)['title'], $nodeResponse['title']);
  }

}

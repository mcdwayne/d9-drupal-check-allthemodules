<?php

namespace Drupal\Tests\shutdown\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the Shutdown module redirects correctly to expected pages.
 *
 * @group shutdown
 */
class RedirectTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'shutdown'];

  /**
   * This test creates simple config on the fly breaking schema checking.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The path to be tested.
   *
   * @var string
   */
  protected $testPath = '';

  /**
   * The path that should be redirected.
   *
   * @var string
   */
  protected $excludedPath = '';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Create a page content type that we will use for testing.
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'page',
        'name' => 'Page',
      ]);
    $type->save();

    // Create the test node.
    $node1 = $this->createNode([
      'title' => 'Homepage',
      'type' => 'page',
    ]);
    $this->testPath = '/node/' . $node1->id();
    $this->config('system.site')->set('site_frontpage', $this->testPath)->save();

    // Create the node that should be excluded from the redirect.
    $node2 = $this->createNode([
      'title' => 'About us',
      'type' => 'page',
    ]);
    $this->excludedPath = '/node/' . $node2->id();
    // Add excluded paths.
    $excludedPaths = $this->excludedPath . PHP_EOL;
    $excludedPaths .= '/user/*';

    // Configure shutdown.
    $config = $this->config('shutdown.settings');
    $config->set('shutdown_enable', 1);
    $config->set('shutdown_redirect_page', '/modules/contrib/shutdown/tests/src/Functional/closed.html');
    $config->set('shutdown_excluded_paths', $excludedPaths);
    $config->save();

    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Tests redirections for authenticated users while site being shut down.
   */
  public function testShutdownRedirectAuthenticated() {
    $account = $this->drupalCreateUser(['navigate shut website']);
    $this->drupalLogin($account);

    // Test that we get the redirect page.
    $this->drupalGet($this->testPath);
    $this->assertSession()->pageTextContains('Homepage');

    // Test that we get the excluded page.
    $this->drupalGet($this->excludedPath);
    $this->assertSession()->pageTextContains('About us');
  }

  /**
   * Tests redirections for anonymous users while site being shut down.
   */
  public function testShutdownRedirectAnonymous() {
    // Test that we get the redirect page.
    $this->drupalGet($this->testPath);
    $this->assertSession()->pageTextContains('The website is closed.');

    // Test that we get the excluded page.
    $this->drupalGet($this->excludedPath);
    $this->assertSession()->pageTextContains('About us');
  }

  /**
   * Tests redirections with wildcards while site being shut down.
   */
  public function testShutdownWildcardRedirect() {
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    // Test that we get the user page.
    $this->drupalGet('/user/2');
    $this->assertSession()->pageTextContains('Member for');
  }

}

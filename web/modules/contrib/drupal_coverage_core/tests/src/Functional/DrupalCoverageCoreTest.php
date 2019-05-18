<?php

namespace Drupal\Tests\drupal_coverage_core\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verify user access to drupal coverage core based on permissions.
 *
 * @group drupal_coverage_core
 */
class DrupalCoverageCoreTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('drupal_coverage_core', 'node');

  /**
   * The user with elevated rights that will be created.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $elevatedRightsUser;

  /**
   * The anonymous user that will be created.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $anonymousUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create users.
    $this->elevatedRightsUser = $this->drupalCreateUser([
      'check code coverage reports status updates',
      'start new code coverage analysis',
    ]);
    $this->anonymousUser = $this->drupalCreateUser(array());
  }

  /**
   * Test all the drupal_coverage_core pages.
   */
  public function testPages() {
    // Create a module node to allow the url /node/{id}/build to be checked. 
    $settings = [
      'type' => 'module',
      'title' => 'Test module',
    ];
    $node = $this->drupalCreateNode($settings);

    // TODO: Figure out why the check page returns 500's. When executing manual
    // testing the permissions work fine, looks like something with the check
    // hook itself. Also looks like the code in the check hook is only executed
    // once. Once this is fixed, re-add 'check to the verifyPages. See #2820654
    // for details about this issue.
    // Admin user should have access.
    $this->drupalLogin($this->rootUser);
    $this->verifyPages(['node/' . $node->id() . '/build']);
    // User with elevated rights should have access.
    $this->drupalLogin($this->elevatedRightsUser);
    $this->verifyPages(['node/' . $node->id() . '/build']);
    // Anonymous user should have no access.
    $this->drupalLogin($this->anonymousUser);
    $this->verifyPages(['check', 'node/' . $node->id() . '/build'], 403);
  }

  /**
   * Verifies the logged in user has access to the various pages.
   *
   * @param array $pages
   *   The array of pages we want to test.
   * @param int $response
   *   (optional) An HTTP response code. Defaults to 200.
   */
  protected function verifyPages($pages, $response = 200) {
    foreach ($pages as $page) {
      $this->drupalGet($page);
      $this->assertSession()->statusCodeEquals($response);
    }
  }

}

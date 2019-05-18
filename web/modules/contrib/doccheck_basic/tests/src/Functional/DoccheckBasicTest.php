<?php

namespace Drupal\Tests\doccheck_basic\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the condition plugins work.
 *
 * @group doccheck_basic
 */
class DoccheckBasicTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'doccheck_basic',
  ];

  /**
   * Use the standard profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $admin;

  /**
   * Doccheck user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $doccheckUser;

  /**
   * Doccheck role.
   *
   * @var string
   */
  protected $doccheckRole = '';

  /**
   * Doccheck node page.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $doccheckNodePage = '';

  /**
   * Doccheck node article.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $doccheckNodeArticle = '';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($this->admin);

    // Create role + user.
    $this->doccheckRole = $this->drupalCreateRole([], NULL);
    $this->doccheckUser = $this->drupalCreateUser(['view own unpublished content'], NULL, FALSE);

    // Create nodes.
    $this->doccheckNodePage = $this->createNode([
      'title' => 'Hello, world!',
      'type' => 'page',
    ]);

    $this->doccheckNodeArticle = $this->createNode([
      'title' => 'Hello, world!',
      'type' => 'article',
    ]);

    // Module settings.
    $this->drupalPostForm('/admin/config/people/doccheckbasic', [
      'template' => 'l_red',
      'user' => $this->doccheckUser->id(),
      'noderedirect' => '/node/' . $this->doccheckNodePage->id(),
    ], 'Save configuration');

    // Create block on article nodes.
    $blockName = strtolower($this->randomMachineName(8));
    $this->drupalPlaceBlock('doccheck_basic', [
      'label' => $blockName,
      'id' => $blockName,
      'region' => 'content',
    ]);
    $this->drupalPostForm('/admin/structure/block/manage/' . $blockName, [
      'visibility[node_type][bundles][article]' => TRUE,
      'visibility[user_role][roles][anonymous]' => TRUE,
    ], 'Save block');

    $this->drupalLogout();
  }

  /**
   * Test the page login.
   */
  public function testPageLogin() {
    // Is not logged in as user.
    $this->drupalGet('/user');
    $this->assertSession()->addressEquals('/user/login');

    $this->drupalGet('/_dc_callback');
    // Does not redirect to specific node.
    $this->assertSession()->addressEquals('/_dc_callback');

    // Login page.
    $this->drupalGet('/doccheck-login');

    // Login / Callback.
    $this->drupalGet('/_dc_callback');
    // Redirect to specific node.
    $this->assertSession()->addressEquals('/node/' . $this->doccheckNodePage->id());

    // Is logged in as user.
    $this->drupalGet('/user');
    $this->assertSession()->addressEquals('/user/' . $this->doccheckUser->id());
  }

  /**
   * Test the block login.
   */
  public function testBlockLogin() {

    // Is not logged in as user.
    $this->drupalGet('/user');
    $this->assertSession()->addressEquals('/user/login');

    $this->drupalGet('/_dc_callback');
    // Does not redirect to specific node.
    $this->assertSession()->addressEquals('/_dc_callback');

    // Login block.
    $this->drupalGet('/node/' . $this->doccheckNodeArticle->id());

    // Login / Callback.
    $this->drupalGet('/_dc_callback');
    // Redirect to specific node.
    $this->assertSession()->addressEquals('/node/' . $this->doccheckNodeArticle->id());

    // Is logged in as user.
    $this->drupalGet('/user');
    $this->assertSession()->addressEquals('/user/' . $this->doccheckUser->id());

  }

}

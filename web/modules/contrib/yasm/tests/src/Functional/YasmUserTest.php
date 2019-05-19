<?php

namespace Drupal\Tests\yasm\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides a class for Yasm functional tests.
 *
 * @group yasm
 */
class YasmUserTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'file', 'user', 'yasm'];

  /**
   * Admin users with all permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Basic user with access to my content pages.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $basicUser;

  /**
   * Denied user without access.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $deniedUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create users.
    $this->adminUser = $this->drupalCreateUser([
      'view the administration theme',
      'access administration pages',
      'yasm summary',
      'yasm contents',
      'yasm users',
      'yasm files',
      'yasm groups',
      'yasm entities',
      'yasm my summary',
      'yasm my contents',
      'yasm my groups',
    ]);
    $this->basicUser = $this->drupalCreateUser([
      'view the administration theme',
      'access administration pages',
      'yasm my summary',
      'yasm my contents',
    ]);
    $this->deniedUser = $this->drupalCreateUser([
      'view the administration theme',
      'access administration pages',
    ]);
  }

  /**
   * Tests users access.
   */
  public function testsUsersAccess() {
    // Tests user without access.
    $this->drupalLogin($this->deniedUser);

    $this->drupalGet('admin/reports/yasm');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/reports/yasm/site/contents');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/reports/yasm/site/users');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/reports/yasm/site/files');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/reports/yasm/site/entities');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/reports/yasm/my');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/reports/yasm/my/contents');
    $this->assertSession()->statusCodeEquals(403);

    // Tests basic user access.
    $this->drupalLogin($this->basicUser);

    $this->drupalGet('admin/reports/yasm');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/reports/yasm/site/contents');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/reports/yasm/site/users');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/reports/yasm/site/files');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/reports/yasm/site/entities');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/reports/yasm/my');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/reports/yasm/my/contents');
    $this->assertSession()->statusCodeEquals(200);

    // Tests admin user access.
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/reports/yasm');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/reports/yasm/site/contents');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/reports/yasm/site/users');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/reports/yasm/site/files');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/reports/yasm/site/entities');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/reports/yasm/my');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/reports/yasm/my/contents');
    $this->assertSession()->statusCodeEquals(200);
    // User have permissions but group module is not enabled.
    $this->drupalGet('admin/reports/yasm/site/groups');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/reports/yasm/my/groups');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests node counts.
   */
  public function testsNodeCounts() {
    // Create 5 articles authored by admin user.
    for ($i = 0; $i < 5; $i++) {
      $node = $this->drupalCreateNode([
        'type' => 'article',
        'uid'  => $this->adminUser->id(),
      ]);
      // Create an extra revision for every node.
      $node->setNewRevision(TRUE);
      $node->save();
    }

    // Create 3 pages authored by basic user.
    for ($i = 0; $i < 3; $i++) {
      $node = $this->drupalCreateNode([
        'type' => 'page',
        'uid'  => $this->basicUser->id(),
      ]);
    }

    // Tests site statistics for admin user.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/reports/yasm');
    $this->assertSession()->pageTextContains('Nodes: 8');

    $this->drupalGet('admin/reports/yasm/site/contents');
    $this->assertSession()->responseContains('<table class="datatable display');

    $this->drupalGet('admin/reports/yasm/site/users');
    $this->assertSession()->responseContains('<table class="datatable display');

    $this->drupalGet('admin/reports/yasm/site/files');
    $this->assertSession()->pageTextContains(t('No data found.'));

    $this->drupalGet('admin/reports/yasm/site/entities');
    $this->assertSession()->responseContains('<table class="datatable display');

    // Tests my statistics for admin user.
    $this->drupalGet('admin/reports/yasm/my');
    $this->assertSession()->pageTextContains('Nodes: 5');

    // Tests my statistics for basic user.
    $this->drupalLogin($this->basicUser);
    $this->drupalGet('admin/reports/yasm/my');
    $this->assertSession()->pageTextContains('Nodes: 3');
  }

}

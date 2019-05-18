<?php

namespace Drupal\Tests\entity_ui\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the admin UI.
 *
 * @group entity_ui
 */
class AdminUITest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    'block',
    'node',
    'field_ui',
    'entity_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an Article node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the entity tab admin UI for nodes.
   */
  public function testNodeEntityTabAdminUI() {
    // Test the node tabs page.
    $this->drupalGet('admin/structure/types/entity_ui');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->drupalCreateUser([
      'administer all entity tabs',
      'administer content types',
    ]));

    $this->drupalGet('admin/structure/types/entity_ui');
    $this->assertSession()->statusCodeEquals(200);

    // Check the collection page has the expected elements.
    // Check the node type collection tab is shown.
    $this->assertLinkByHref('admin/structure/types');

    // Check the built-in tabs on nodes are shown.
    $this->assertText(t('View'));
    $this->assertText(t('Edit'));
    $this->assertText(t('Delete'));

    $this->assertText(t('Add entity tab'));
    $this->assertLinkByHref('admin/structure/entity_ui/entity_tab/add/node');

    // Create a new entity tab on nodes.
    $this->clickLink(t('Add entity tab'));
    // Select the content plugin.
    $this->clickLink(t('Entity view'));

    $edit = [
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'tab_title' => $this->randomString(),
      'page_title' => $this->randomString(),
      'path' => $this->randomString(),
      'target_bundles[article]' => 0,
      'content_plugin' => 'entity_view',
      'content_config[view_mode]' => 'default',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check the new tab is now shown in the collection listing.
    $this->assertSession()->pageTextContains($edit['label']);
    $this->assertLinkByHref("admin/structure/entity_ui/entity_tab/node.{$edit['id']}/edit");


    // todo:
    // - changing weights works
  }

  /**
   * Tests the entity tab admin UI for users.
   */
  public function testUserEntityTabAdminUI() {
    // Test the user tabs page.
    $this->drupalGet('admin/config/people/accounts/entity_ui');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->drupalCreateUser([
      'administer all entity tabs',
      'administer account settings',
    ]));

    $this->drupalGet('admin/config/people/accounts/entity_ui');
    $this->assertSession()->statusCodeEquals(200);

    // Check the collection page has the expected elements.
    // Check the user accounts setings tab is shown.
    $this->assertLinkByHref('admin/config/people/accounts');

    // Check the built-in tabs on users are shown.
    $this->assertText(t('View'));
    $this->assertText(t('Edit'));

    $this->assertText(t('Add entity tab'));
    $this->assertLinkByHref('admin/structure/entity_ui/entity_tab/add/user');

    // Create a new entity tab on users.
    $this->clickLink(t('Add entity tab'));
    // Select the content plugin.
    $this->clickLink(t('Entity view'));

    $edit = [
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'tab_title' => $this->randomString(),
      'page_title' => $this->randomString(),
      'path' => $this->randomString(),
      'content_plugin' => 'entity_view',
      'content_config[view_mode]' => 'default',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check the new tab is now shown in the collection listing.
    $this->assertSession()->pageTextContains($edit['label']);
    $this->assertLinkByHref("admin/structure/entity_ui/entity_tab/user.{$edit['id']}/edit");


    // todo:
    // - changing weights works
  }

}

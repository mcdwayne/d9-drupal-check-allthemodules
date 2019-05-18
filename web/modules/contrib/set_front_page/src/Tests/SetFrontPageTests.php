<?php

namespace Drupal\set_front_page\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Tests for set front page module.
 *
 * @group set_front_page
 */
class SetFrontPageTests extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['views', 'node', 'set_front_page'];

  /**
   * The node object that is created for testing.
   *
   * @var string
   */
  protected $node;

  /**
   * The path to a node that is created for testing.
   *
   * @var string
   */
  protected $nodePath;

  /**
   * The path to a term that is created for testing.
   *
   * @var string
   */
  protected $termPath;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create admin user, log in admin user, and create one node.
    $this->drupalLogin ($this->drupalCreateUser([
      'access content',
      'administer site configuration',
      'set front page'
    ]));
    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalCreateContentType(['type' => 'blog']);
    $this->node = $this->drupalCreateNode(['type' => 'page', 'promote' => 1]);
    $this->nodePath = 'node/' . $this->node->id();

    // Configure 'node' as front page.
    $this->config('system.site')->set('page.front', '/node')->save();
  }

  /**
   * Test override front page functionality.
   */
  public function testSetFrontPageConfig() {
    // Test default homepage.
    $this->drupalGet('');
    $this->assertTitle('Home | Drupal');

    // Change the front page to an invalid path.
    $edit = ['site_frontpage' => '/kittens'];
    $this->drupalPostForm('admin/config/set_front_page/settings', $edit, t('Save configuration'));
    $this->assertText(t("The path '@path' is either invalid or you do not have access to it.", ['@path' => $edit['site_frontpage']]));
    // Change the front page to a valid path without a starting slash.
    $edit = ['site_frontpage' => $this->nodePath];
    $this->drupalPostForm('admin/config/set_front_page/settings', $edit, t('Save configuration'));
    $this->assertRaw(SafeMarkup::format("The path '%path' has to start with a slash.", ['%path' => $edit['site_frontpage']]));
    // Change the front page to a valid path.
    $edit['site_frontpage'] = '/' . $this->nodePath;
    $this->drupalPostForm('admin/config/set_front_page/settings', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'), 'The configuration have been saved.');
    // The homepage is $this->node and its title changed.
    $this->drupalGet('');
    $this->assertTitle($this->node->getTitle() . ' | Drupal');

    // Configure a default frontpage path with an invalid path.
    $edit = ['site_frontpage_default' => '/kittens'];
    $this->drupalPostForm('admin/config/set_front_page/settings', $edit, t('Save configuration'));
    $this->assertText(t("The path '@path' is either invalid or you do not have access to it.", ['@path' => $edit['site_frontpage_default']]));
    // Configure a default frontpage path with a valid path without starting
    // slash.
    $edit = ['site_frontpage_default' => $this->nodePath];
    $this->drupalPostForm('admin/config/set_front_page/settings', $edit, t('Save configuration'));
    $this->assertRaw(SafeMarkup::format("The path '%path' has to start with a slash.", ['%path' => $edit['site_frontpage_default']]));
    // Change the default front page to a valid path.
    $edit['site_frontpage_default'] = '/node'; // . $this->nodePath;
    $this->drupalPostForm('admin/config/set_front_page/settings', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    // The set frontpage tab is not accessible by this user, because the
    // content type is not enabled in the set front page configuration.
    $this->drupalGet($this->nodePath . '/set_front_page');
    $this->assertResponse(403, 'The set front page is not available to the user');

    // Enabled valid content type to be an homepage.
    $edit['set_front_page_node_type__page'] = TRUE;
    $this->drupalPostForm('admin/config/set_front_page/settings', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    // The set frontpage tab is accessible by this user
    $this->drupalGet($this->nodePath . '/set_front_page');
    $this->assertResponse(200, 'The set front page is available to the user');
    // The icorrent node is the frontapge so the save button is disabled.
    $this->assertFieldByXpath('//input[@type="submit" and @disabled="disabled"]', 'Use this page as the front page');
    // The default buttons is enabled.
    $this->assertFieldByXpath('//input[@type="submit" and not(@disabled)]', 'Revert to the default page');

    // In the content type blog the set frontpage is not enabled.
    $blog_node = $this->drupalCreateNode(['type' => 'blog', 'promote' => 1]);
    // The set frontpage tab is not accessible by this user
    $this->drupalGet('node/' . $blog_node->id() . '/set_front_page');
    $this->assertResponse(403, 'The set front page is not available to the user');

    // Create a new node.
    $node = $this->drupalCreateNode(['type' => 'page', 'promote' => 1]);
    // The set frontpage tab is accessible by this user
    $this->drupalGet('node/' . $node->id() . '/set_front_page');
    $this->assertResponse(200, 'The set front page is available to the user');
    // The frontapge is not the current node, so is enabled.
    $this->assertFieldByXpath('//input[@type="submit" and not(@disabled)]', 'Use this page as the front page');
    // The default buttons is enabled.
    $this->assertFieldByXpath('//input[@type="submit" and not(@disabled)]', 'Revert to the default page');
    // Change the front page to $node.
    $this->drupalPostForm('node/' . $node->id() . '/set_front_page', [], t('Use this page as the front page'));
    // The frontapge is the current node, so is disabled.
    $this->assertFieldByXpath('//input[@type="submit" and @disabled="disabled"]', 'Use this page as the front page');
    // The default buttons is enabled.
    $this->assertFieldByXpath('//input[@type="submit" and not(@disabled)]', 'Revert to the default page');
    $this->drupalGet('');
    $this->assertTitle($node->getTitle() . ' | Drupal');

    // Change the default frontpage path.
    $edit = ['site_frontpage_default' => '/' . $this->nodePath];
    $this->drupalPostForm('admin/config/set_front_page/settings', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));
    $this->drupalGet($this->nodePath . '/set_front_page');
    $this->assertResponse(200, 'The set front page is available to the user');
    // The current node is the frontpage so disable the save button.
    $this->assertFieldByXpath('//input[@type="submit" and not(@disabled)]', 'Use this page as the front page');
    // The current node is the default frontpage so the button is disabled.
    $this->assertFieldByXpath('//input[@type="submit" and @disabled = "disabled"]', 'Revert to the default page');

    // If the default frontpage path is not defined, the related button should
    // disappear.
    $edit = ['site_frontpage_default' => ''];
    $this->drupalPostForm('admin/config/set_front_page/settings', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));
    $this->drupalGet($this->nodePath . '/set_front_page');
    $this->assertNoFieldByXpath('//input[@type="submit"]', 'Revert to the default page');
  }
}


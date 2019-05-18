<?php

namespace Drupal\Tests\parsely_tag\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the basic functions of the Parse.ly Tag module.
 *
 * @group Lightbox Campaigns
 */
class ParselyTagTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['node', 'token', 'parsely_tag'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create article content type.
    /** @var \Drupal\node\Entity\NodeType $node_type */
    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();
  }

  /**
   * Test global configuration.
   */
  public function testGlobalConfiguration() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $admin_user = $this->drupalCreateUser(['administer parsely tag']);

    // Anonymous user should not have access.
    $this->drupalGet('/admin/config/search/parsely_tag');
    $assert->statusCodeEquals(403);

    $this->drupalLogin($admin_user);

    // Admin user should have access.
    $this->drupalGet('/admin/config/search/parsely_tag');
    $assert->statusCodeEquals(200);

    // Verify default field values.
    $assert->fieldValueEquals('site_id', '');
    $assert->fieldValueEquals('enable', 1);
    $assert->fieldValueEquals('type', 'NewsArticle');
    $assert->fieldValueEquals('headline', '[node:title]');
    $assert->fieldValueEquals('url', '[node:url]');
    $assert->fieldValueEquals('date_created', '[node:created]');
    $assert->fieldValueEquals('article_section', '');
    $assert->fieldValueEquals('creator', '[node:author]');
    $assert->fieldValueEquals('keywords', '');

    // Update default values.
    $edit = [
      'site_id' => 'test.site',
      'enable' => 1,
      'type' => 'NewsArticle',
      'headline' => '[node:title]',
      'url' => '[node:url]',
      'date_created' => '[node:created]',
      'article_section' => 'Default',
      'creator' => '[node:author]',
      'keywords' => 'None',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $assert->fieldValueEquals('site_id', 'test.site');
    $assert->fieldValueEquals('article_section', 'Default');
    $assert->fieldValueEquals('keywords', 'None');
  }

  /**
   * Test Content Type specific configuration.
   */
  public function testContentTypeConfiguration() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $content_type_admin_user = $this->drupalCreateUser([
      'administer content types',
      'administer nodes',
    ]);

    $parsely_admin_user = $this->drupalCreateUser([
      'administer content types',
      'administer nodes',
      'edit article parsely tag settings',
    ]);

    // Content type admin should not have access to Parse.ly Tag settings for
    // Article content type.
    $this->drupalLogin($content_type_admin_user);
    $this->drupalGet('/admin/structure/types/manage/article');
    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('Parse.ly Tag settings');
    $this->drupalLogout();

    // Parse.ly admin should have access to Parse.ly Tag settings for Article
    // content type.
    $this->drupalLogin($parsely_admin_user);
    // User should not have access to Parse.ly Tag settings for Article.
    $this->drupalGet('/admin/structure/types/manage/article');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Parse.ly Tag settings');

    // Verify default field values.
    $assert->fieldValueEquals('parsely_tag_enable', 1);
    $assert->fieldValueEquals('parsely_tag_type', 'NewsArticle');
    $assert->fieldValueEquals('parsely_tag_headline', '[node:title]');
    $assert->fieldValueEquals('parsely_tag_url', '[node:url]');
    $assert->fieldValueEquals('parsely_tag_date_created', '[node:created]');
    $assert->fieldValueEquals('parsely_tag_article_section', '');
    $assert->fieldValueEquals('parsely_tag_creator', '[node:author]');
    $assert->fieldValueEquals('parsely_tag_keywords', '');

    // Update values.
    $edit = [
      'parsely_tag_enable' => 1,
      'parsely_tag_type' => 'NewsArticle',
      'parsely_tag_headline' => 'Article title',
      'parsely_tag_url' => '[node:url]',
      'parsely_tag_date_created' => '[node:created]',
      'parsely_tag_article_section' => 'Articles',
      'parsely_tag_creator' => '[current-user]',
      'parsely_tag_keywords' => 'Article',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save content type');

    // Verify updated values.
    $this->drupalGet('/admin/structure/types/manage/article');
    $assert->fieldValueEquals('parsely_tag_headline', 'Article title');
    $assert->fieldValueEquals('parsely_tag_article_section', 'Articles');
    $assert->fieldValueEquals('parsely_tag_creator', '[current-user]');
    $assert->fieldValueEquals('parsely_tag_keywords', 'Article');
  }

}

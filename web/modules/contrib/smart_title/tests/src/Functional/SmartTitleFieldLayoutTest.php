<?php

namespace Drupal\Tests\smart_title\Functional;

/**
 * Tests the module's title placement function.
 *
 * @group smart_title
 */
class SmartTitleFieldLayoutTest extends SmartTitleBrowserTestBase {

  /**
   * The modules to be loaded for this test.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'field_ui',
    'field_layout',
    'node',
    'smart_title',
    'smart_title_ui',
    'views',
  ];

  /**
   * Tests that Smart Title works properly with Field Layout.
   */
  public function testSmartTitlePlacement() {
    $this->drupalLogin($this->adminUser);

    // Enable Smart Title for test_page teaser display mode and make it visible.
    $this->drupalPostForm('admin/structure/types/manage/test_page/display/teaser', [
      'smart_title__enabled' => TRUE,
    ], 'Save');

    // Change layout for teaser view mode.
    $form_edit = [
      'field_layout' => 'layout_twocol',
    ];
    $this->drupalPostForm('admin/structure/types/manage/test_page/display/teaser', $form_edit, 'Change layout');
    $this->drupalPostForm(NULL, [], 'Save');

    // Make Smart Title visible for teaser view mode with custom configuration.
    $this->drupalPostForm(NULL, [
      'fields[smart_title][region]' => 'second',
    ], 'Save');
    $this->click('[name="smart_title_settings_edit"]');
    $this->drupalPostForm(NULL, [
      'fields[smart_title][settings_edit_form][settings][smart_title__tag]' => 'h3',
      'fields[smart_title][settings_edit_form][settings][smart_title__classes]' => 'smart-title--test',
    ], 'Save');

    // Test that Smart Title is displayed on the front page (teaser view mode)
    // in the corresponding field layout region for admin user.
    $this->drupalGet('node');
    $this->assertSession()->pageTextContains($this->testPageNode->label());
    $article_title = $this->xpath($this->cssSelectToXpath('article .layout__region--second h3.smart-title--test'));
    $this->assertEquals($this->testPageNode->label(), $article_title[0]->getText());

    // Default title isn't displayed on the front page for admin user.
    $this->drupalGet('node');
    $article_title = $this->xpath($this->cssSelectToXpath('article > h2'));
    $this->assertEquals($article_title, []);

    $this->drupalLogout();

    // Smart Title is displayed on the front page (teaser vm) in the
    // corresponding field layout region for anonymous user.
    $this->drupalGet('node');
    $this->assertSession()->pageTextContains($this->testPageNode->label());
    $article_title = $this->xpath($this->cssSelectToXpath('article .layout__region--second h3.smart-title--test'));
    $this->assertEquals($this->testPageNode->label(), $article_title[0]->getText());

    // Default title isn't displayed on the front page for anonymous user.
    $this->drupalGet('node');
    $article_title = $this->xpath($this->cssSelectToXpath('article > h2'));
    $this->assertEquals($article_title, []);
  }

}

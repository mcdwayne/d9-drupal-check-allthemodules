<?php

namespace Drupal\Tests\block_style_plugins\FunctionalJavascript;

use Drupal\Tests\layout_builder\FunctionalJavascript\InlineBlockTestBase;

/**
 * Tests that the inline block feature works with styles.
 *
 * @group block_style_plugins
 */
class InlineBlockTest extends InlineBlockTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'layout_builder',
    'block_style_plugins',
    'block_style_plugins_test',
    'contextual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $user = $this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
      'access contextual links',
    ]);
    $user->save();
    $this->drupalLogin($user);

    // Enable layout builder.
    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    $this->drupalPostForm("$field_ui_prefix/display/default", [
      'layout[enabled]' => TRUE,
      'layout[allow_custom]' => TRUE,
    ], 'Save');
  }

  /**
   * Tests that styles are correctly included/excluded from inline blocks.
   */
  public function testInlineBlocksVisibility() {
    $assert = $this->assertSession();

    $this->drupalGet('node/1/layout');

    // Add a basic block with the body field set.
    $this->addInlineBlockToLayout('Block title', 'The DEFAULT block body');

    // Click the contextual link.
    $this->clickContextualLink(static::INLINE_BLOCK_LOCATOR, 'Style settings');
    $assert->assertWaitOnAjaxRequest();

    // There should not be an option for the excluded style.
    $assert->pageTextContains('Simple Class');
    $assert->pageTextContains('Dropdown with Include');
    $assert->pageTextNotContains('Checkbox with Exclude');
    $assert->pageTextNotContains('Styles Created by Yaml');
  }

}

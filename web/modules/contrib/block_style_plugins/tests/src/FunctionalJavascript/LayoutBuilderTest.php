<?php

namespace Drupal\Tests\block_style_plugins\FunctionalJavascript;

use Drupal\block_content\Entity\BlockContent;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;

/**
 * Layout Builder tests.
 *
 * @group block_style_plugins
 */
class LayoutBuilderTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;

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
    $this->createContentType(['type' => 'bundle_with_section_field']);

    // The Layout Builder UI relies on local tasks.
    $this->drupalPlaceBlock('local_tasks_block');

    // Enable layout builder.
    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    $this->drupalPostForm("$field_ui_prefix/display/default", [
      'layout[enabled]' => TRUE,
      'layout[allow_custom]' => TRUE,
    ], 'Save');

    // Start by creating a node.
    $node = $this->createNode([
      'type' => 'bundle_with_section_field',
      'body' => [
        [
          'value' => 'The node body',
        ],
      ],
    ]);
    $node->save();
  }

  /**
   * Tests that styles can be applied via Layout Builder.
   */
  public function testLayoutBuilderUi() {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $block_css_locator = '.block-system-powered-by-block';

    $this->drupalGet('node/1/layout');
    $assert->pageTextContains('The node body');

    // Add a new block.
    $this->addNewBlock('Powered by Drupal');

    // Click the contextual link.
    $this->clickContextualLink($block_css_locator, 'Style settings');

    // Choose a style to apply.
    $dropdown = $assert->waitForElementVisible('css', '[name="block_styles"]');
    $dropdown->selectOption('Simple Class');
    $page->pressButton('Add Style');
    $assert->assertWaitOnAjaxRequest();

    // Configure the styles.
    $page->fillField('settings[simple_class]', 'test-class');
    $page->pressButton('Add Styles');

    // Check to see if classes were applied.
    $block_element = $assert->waitForElementVisible('css', $block_css_locator);
    $block_element->hasClass('test-class');

    // Edit the style.
    $this->clickContextualLink($block_css_locator, 'Style settings');
    $assert->assertWaitOnAjaxRequest();

    $this->clickLink('Edit');
    $assert->assertWaitOnAjaxRequest();
    $assert->fieldValueEquals('settings[simple_class]', 'test-class');
    $page->fillField('settings[simple_class]', 'edited-class');
    $page->pressButton('Update');
    $assert->assertWaitOnAjaxRequest();

    // Save the Layout.
    $this->clickLink('Save Layout');
    // Check to see if classes are still applied.
    $block_element = $assert->waitForElementVisible('css', $block_css_locator);
    $block_element->hasClass('edited-class');

    // Delete the style.
    $this->drupalGet('node/1/layout');
    $this->clickContextualLink($block_css_locator, 'Style settings');
    $assert->assertWaitOnAjaxRequest();

    $this->clickLink('Delete');
    $assert->assertWaitOnAjaxRequest();
    $page->pressButton('Cancel');
    $assert->assertWaitOnAjaxRequest();
    $this->clickLink('Delete');
    $assert->assertWaitOnAjaxRequest();
    $page->pressButton('Confirm');

    // Check to see if classes have been removed.
    $assert->responseNotContains('edited-class');
    // Save the Layout.
    $this->clickLink('Save Layout');
    // Check to see if classes have been removed.
    $assert->responseContains('The layout override has been saved');
    $assert->responseNotContains('edited-class');
  }

  /**
   * Tests that styles are correctly include/excluded from normal block.
   */
  public function testLayoutBuilderBlockVisibility() {
    $assert = $this->assertSession();
    $block_css_locator = '.block-system-powered-by-block';

    $this->drupalGet('node/1/layout');

    // Add a new block.
    $this->addNewBlock('Powered by Drupal');

    // Click the contextual link.
    $this->clickContextualLink($block_css_locator, 'Style settings');
    $assert->assertWaitOnAjaxRequest();

    // There should not be an option for the excluded style.
    $assert->pageTextContains('Styles Created by Yaml');
    $assert->pageTextContains('Dropdown with Include');
    $assert->pageTextNotContains('Checkbox with Exclude');
  }

  /**
   * Tests that styles are correctly included/excluded from custom blocks.
   */
  public function testLayoutBuilderCustomBlockVisibility() {
    $assert = $this->assertSession();

    // Create the custom block.
    $block = $this->createBlockContent('Custom Block Test');
    $block_css_locator = '.block-block-content' . $block->uuid();

    $this->drupalGet('node/1/layout');

    // Add a new block.
    $this->addNewBlock('Custom Block Test');

    // Click the contextual link.
    $this->clickContextualLink($block_css_locator, 'Style settings');
    $assert->assertWaitOnAjaxRequest();

    // There should not be an option for the excluded style.
    $assert->pageTextContains('Simple Class');
    $assert->pageTextContains('Dropdown with Include');
    $assert->pageTextNotContains('Checkbox with Exclude');
    $assert->pageTextNotContains('Styles Created by Yaml');
  }

  /**
   * Tests that a template being set will work with Layout Builder.
   */
  public function testLayoutBuilderTemplateSet() {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $block_css_locator = '.block-system-powered-by-block';

    $this->drupalGet('node/1/layout');

    // Add a new block.
    $this->addNewBlock('Powered by Drupal');

    // Click the contextual link.
    $this->clickContextualLink($block_css_locator, 'Style settings');

    // Choose a style to apply.
    $dropdown = $assert->waitForElementVisible('css', '[name="block_styles"]');
    $dropdown->selectOption('Template Set by Yaml');
    $page->pressButton('Add Style');
    $assert->assertWaitOnAjaxRequest();

    // Configure the styles.
    $page->fillField('settings[test_field]', 'test-class');
    $page->pressButton('Add Styles');

    // Assert that the template applied.
    $assert->responseContains('This is a custom template');

    // Save the Layout.
    $this->clickLink('Save Layout');
    // Check to see if the template is still applied.
    $assert->responseContains('This is a custom template');
  }

  /**
   * Add a block from the Off-Canvas Tray into a Section.
   *
   * @param string $title
   *   The title of the Block being added.
   */
  protected function addNewBlock($title) {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->clickLink('Add Block');
    $assert->assertWaitOnAjaxRequest();
    $this->clickLink($title);
    $assert->assertWaitOnAjaxRequest();
    $page->pressButton('Add Block');
    $assert->assertWaitOnAjaxRequest();
  }

  /**
   * Creates a custom block.
   *
   * @param bool|string $title
   *   (optional) Title of block. When no value is given uses a random name.
   *   Defaults to FALSE.
   * @param string $bundle
   *   (optional) Bundle name. Defaults to 'basic'.
   * @param bool $save
   *   (optional) Whether to save the block. Defaults to TRUE.
   *
   * @return \Drupal\block_content\Entity\BlockContent
   *   Created custom block.
   */
  protected function createBlockContent($title = FALSE, $bundle = 'basic', $save = TRUE) {
    $title = $title ?: $this->randomMachineName();
    $block_content = BlockContent::create([
      'info' => $title,
      'type' => $bundle,
      'langcode' => 'en',
    ]);
    if ($block_content && $save === TRUE) {
      $block_content->save();
    }
    return $block_content;
  }

}

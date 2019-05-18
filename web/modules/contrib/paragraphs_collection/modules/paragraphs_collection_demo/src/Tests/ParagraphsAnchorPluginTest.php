<?php

namespace Drupal\paragraphs_collection_demo\Tests;

use Drupal\paragraphs\Tests\Experimental\ParagraphsExperimentalTestBase;

/**
 * Tests the anchor plugin.
 *
 * @see \Drupal\paragraphs_collection_demo\Plugin\paragraphs\Behavior\ParagraphsAnchorPlugin
 * @group paragraphs_collection_demo
 */
class ParagraphsAnchorPluginTest extends ParagraphsExperimentalTestBase {

  /**
   * Modules to be enabled.
   *
   * @var array
   */
  public static $modules = [
    'paragraphs_collection_demo',
  ];

  /**
   * Tests the anchor plugin functionality.
   */
  public function testAnchorPlugin() {
    $this->loginAsAdmin(['edit behavior plugin settings']);

    $this->drupalGet('admin/structure/paragraphs_type/add');
    $this->assertText('Anchor');
    $this->assertText('Allows to set ID attribute that can be used as jump position in URLs.');

    $paragraph_type = 'text_test';
    $this->addParagraphsType($paragraph_type);
    $bundle_path = 'admin/structure/paragraphs_type/' . $paragraph_type;
    $this->fieldUIAddExistingField('admin/structure/paragraphs_type/' . $paragraph_type, 'paragraphs_text');

    $this->drupalGet($bundle_path);
    $edit = [
      'behavior_plugins[anchor][enabled]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->addParagraphedContentType('paragraphed_test', 'text');
    $this->setParagraphsWidgetMode('paragraphed_test', 'text', 'closed');
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
    ]);
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'text_text_test_add_more');
    $this->assertText('Anchor');
    $this->assertText('Sets an ID attribute prefixed with "scrollto-" in the Paragraph so that it can be used as a jump-to link.');
    $edit = [
      'title[0][value]' => t('Anchor'),
      'text[0][subform][paragraphs_text][0][value]' => t('Test Anchor'),
      'text[0][behavior_plugins][anchor][anchor]' => 'element-anchor',
    ];

    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertRaw('id="scrollto-element-anchor"');

    // Test settings summary.
    $this->clickLink('Edit');
    $this->assertRaw('<span class="summary-content">Test Anchor</span></div><div class="paragraphs-plugin-wrapper"><span class="summary-plugin"><span class="summary-plugin-label">Anchor</span>scrollto-element-anchor');
  }

}

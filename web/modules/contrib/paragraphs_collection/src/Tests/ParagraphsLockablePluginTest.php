<?php

namespace Drupal\paragraphs_collection\Tests;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\paragraphs\Tests\Experimental\ParagraphsExperimentalTestBase;

/**
 * Tests the lockable plugin.
 *
 * @see \Drupal\paragraphs_collection\Plugin\paragraphs\Behavior\ParagraphsLockablePlugin
 * @group paragraphs_collection
 */
class ParagraphsLockablePluginTest extends ParagraphsExperimentalTestBase {

  use FieldUiTestTrait;

  /**
   * Required modules to be installed for test to run.
   *
   * @var array
   */
  public static $modules = [
    'paragraphs_collection',
  ];

  /**
   * Tests the lockable functionality with admin and other role on paragraphs.
   */
  public function testLockedParagraphInstance() {

    // Create an article with paragraphs field.
    $contentTypeId = 'paragraphed_lock_test';
    $this->addParagraphedContentType($contentTypeId, 'paragraphs');

    $permissions = [
      'administer site configuration',
      'administer lockable paragraph',
      'bypass node access',
      'administer content types',
      'edit behavior plugin settings',
    ];
    $this->loginAsAdmin($permissions);

    // Add a text paragraphs type with a text field.
    $paragraphType = 'text_test';
    $fieldName = 'text';
    $this->addParagraphsType($paragraphType);
    $bundlePath = 'admin/structure/paragraphs_type/' . $paragraphType;
    $this->fieldUIAddExistingField('admin/structure/paragraphs_type/' . $paragraphType, 'paragraphs_text');

    $this->drupalGet($bundlePath);
    $this->assertFieldByName('behavior_plugins[lockable][enabled]');
    $edit = [
      'behavior_plugins[lockable][enabled]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check that the bundle now has lockable enabled.
    $this->drupalGet($bundlePath);
    $this->assertFieldChecked('edit-behavior-plugins-lockable-enabled');

    // Create a paragraphed content.
    $this->drupalGet('node/add/' . $contentTypeId);
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_' . $paragraphType . '_add_more');

    // Add title and body text to the node and save it.
    $edit = [
      'title[0][value]' => 'Test article',
      'paragraphs[0][subform][paragraphs_' . $fieldName . '][0][value]' => 'This is some text',
      'paragraphs[0][behavior_plugins][lockable][locked]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $nodeUrl = $this->getUrl();

    $this->drupalGet($nodeUrl . '/edit');
    $this->assertNoText('You are not allowed to edit or remove this Paragraph.');

    // Check that a new user without our permission cannot edit.
    $account = $this->drupalCreateUser(['bypass node access']);
    $this->drupalLogin($account);
    $this->drupalGet($nodeUrl . '/edit');
    $this->assertText('You are not allowed to edit or remove this Paragraph.');

    // Check that a new non admin user who does have the permission can edit.
    $account = $this->drupalCreateUser(['bypass node access', 'administer lockable paragraph']);
    $this->drupalLogin($account);
    $this->drupalGet($nodeUrl . '/edit');
    $this->assertNoText('You are not allowed to edit or remove this Paragraph.');

  }

  /**
   * Tests Lockable plugin summary for paragraphs closed mode.
   */
  public function testLockedSettingsSummary() {
    // Create an article with paragraphs field.
    $content_type_id = 'paragraphed_lock_test';
    $this->addParagraphedContentType($content_type_id, 'paragraphs');
    $this->loginAsAdmin([
      'administer site configuration',
      'administer lockable paragraph',
      'bypass node access',
      'administer content types',
      'edit behavior plugin settings',
      'create ' . $content_type_id . ' content',
      'edit any ' . $content_type_id . ' content',
    ]);

    // Add a text paragraph type.
    $paragraph_type = 'text_test';
    $field_name = 'text';
    $this->addParagraphsType($paragraph_type);
    $this->fieldUIAddExistingField('admin/structure/paragraphs_type/' . $paragraph_type, 'paragraphs_text');
    $this->setParagraphsWidgetMode($content_type_id, 'paragraphs', 'closed');
    // Enable Lockable plugin for this text paragraph type.
    $edit = ['behavior_plugins[lockable][enabled]' => TRUE];
    $this->drupalPostForm('admin/structure/paragraphs_type/' . $paragraph_type, $edit, t('Save'));

    // Node edit: add two text paragraph type, set the second text as locked.
    $this->drupalGet('node/add/' . $content_type_id);
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_' . $paragraph_type . '_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_' . $paragraph_type . '_add_more');
    $edit = [
      'title[0][value]' => 'Lockable plugin summary',
      'paragraphs[0][subform][paragraphs_' . $field_name . '][0][value]' => 'Text 1',
      'paragraphs[1][subform][paragraphs_' . $field_name . '][0][value]' => 'Text 2',
      'paragraphs[1][behavior_plugins][lockable][locked]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Assert the paragraph item summaries include the plugin summaries.
    $this->clickLink('Edit');
    $unicode_closed_lock = json_decode('"\ud83d\udd12"');
    $this->assertRaw('<span class="summary-content">Text 1<');
    $this->assertRaw('<span class="summary-content">Text 2</span></div><div class="paragraphs-plugin-wrapper"><span class="summary-plugin">' . $unicode_closed_lock . '<');
  }

}

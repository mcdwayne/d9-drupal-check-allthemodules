<?php

namespace Drupal\Tests\media_reference_revisions\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test integration with Paragraphs.
 *
 * @group media_reference_revisions
 */
class ParagraphsIntegrationTest extends BrowserTestBase {

  use MediaReferenceRevisionsHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    // Core requirements.
    'entity_reference',
    'field',
    'field_ui',
    'node',
    'user',

    // Contrib requirements.
    'entity',
    'media_entity',

    // Extra modules for this test.
    'paragraphs',

    // This module.
    'media_reference_revisions',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Log in as user 1.
    $this->loginUser1();

    // Create the necessary structures for testing.
    $this->setupParagraphEntities();
  }

  /**
   * Confirm the Paragraphs integration.
   */
  public function testItWorks() {
    $expected = [
      [
        'id' => 1,
        'entity_type' => 'paragraph',
        'entity_id' => 1,
        'entity_vid' => 1,
        'media_id' => 1,
        'media_vid' => 1,
      ],
      [
        'id' => 2,
        'entity_type' => 'paragraph',
        'entity_id' => 1,
        'entity_vid' => 2,
        'media_id' => 1,
        'media_vid' => 2,
      ],
    ];

    // Create the media object.
    $this->drupalGet('media/add/test_media');
    $this->assertResponse(200);
    $edit = [
      'name[0][value]' => 'Test media',
      'field_text[0][value]' => 'Just a little test media item.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save and publish');
    $this->assertResponse(200);
    $this->assertText('Test Media Test media has been created.');
    $this->assertText('Just a little test media item.');

    // Load the media object's system path, to make sure the correct one is
    // loaded.
    $this->drupalGet('media/1');
    $this->assertResponse(200);
    $this->assertText('Test media');
    $this->assertText('Just a little test media item.');

    // Create the node with a reference to the media object.
    $this->drupalGet('node/add/test_mrr');
    $this->assertResponse(200);
    $edit = [
      'title[0][value]' => 'Test node',
      'body[0][value]' => 'Just a little test node.',
      // 'body[0][format]' => 'basic_html',
      'field_paragraphs[0][subform][field_media][0][target_id]' => 'Test media (1)',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertResponse(200);
    $this->assertText('Test MRR Test node has been created.');
    $this->assertText('Just a little test node.');
    $this->assertText('Test media');

    // Load the node's system path, to make sure the correct one is loaded.
    $this->drupalGet('node/1');
    $this->assertResponse(200);
    $this->assertText('Test node');
    $this->assertText('Just a little test node.');
    $this->assertText('Test media');

    // Confirm that an MRR record was created for the above.
    $results = $this->getAllMrrRecords();

    // There should be one record for the above entities.
    $this->assertEqual(count($results), 1);
    foreach ($results as $key => $record) {
      $this->verbose($record);
      $this->assertEqual((array)$record, $expected[$key]);
    }

    // Create a new revision of the media object.
    $this->drupalGet('media/1/edit');
    $edit = [
      'name[0][value]' => 'Changed test media',
      'field_text[0][value]' => 'Just a little changed test media item.',
      'revision' => TRUE,
      'revision_log' => 'Testing creation of a new revision',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save and keep published');
    $this->assertResponse(200);
    $this->assertText('Test Media Changed test media has been updated.');
    $this->assertText('Just a little changed test media item.');
    // Make sure the old text is gone.
    $this->assertNoText('Test Media Test media has been updated.');
    $this->assertNoText('Just a little test media item.');

    // Verify that the MRR data is correct.
    $results = $this->getAllMrrRecords();
    $this->assertEqual(count($results), 1);
    foreach ($results as $key => $record) {
      $this->verbose($record);
      $this->assertEqual((array)$record, $expected[$key]);
    }

    // Confirm that the node still loads the original media object revision.
    $this->drupalGet('node/1');
    $this->assertResponse(200);
    $this->assertText('Test node');
    $this->assertText('Just a little test node.');
    // Confirm the old media object revision is loaded.
    $this->assertText('Test media');
    // Confirm that the new media object revision isn't loaded.
    $this->assertNoText('Changed test media');

    // Clear the caches.
    drupal_flush_all_caches();

    // Confirm that the node still loads the original media object revision.
    $this->drupalGet('node/1');
    $this->assertResponse(200);
    $this->assertText('Test node');
    $this->assertText('Just a little test node.');
    // Confirm the old media object revision is loaded.
    $this->assertText('Test media');
    // Confirm that the new media object revision isn't loaded.
    $this->assertNoText('Changed test media');

    // Update the node display settings.
    $this->changeMediaDisplaySettings('view', 'admin/structure/paragraphs_type/media_test/display');

    // Load the node again, confirm that the original values are displayed.
    $this->drupalGet('node/1');
    $this->assertResponse(200);
    $this->assertText('Test node');
    $this->assertText('Just a little test node.');
    // Confirm the old media object revision is loaded.
    $this->assertText('Just a little test media item.');
    // Confirm that the new media object revision isn't loaded.
    $this->assertNoText('Just a little changed test media item.');

    // Update the node.
    $this->drupalGet('node/1/edit');
    $this->assertResponse(200);
    $edit = [
      'title[0][value]' => 'Changed test node',
      'body[0][value]' => 'Just a little changed test node.',
      'revision' => TRUE,
      'revision_log[0][value]' => 'Testing creation of a new revision',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertResponse(200);

    // This will be updated as the node was published.
    $expected[0]['media_vid'] = 2;

    // Verify that the MRR data is correct.
    $results = $this->getAllMrrRecords();
    $this->assertEqual(count($results), 2);
    foreach ($results as $key => $record) {
      $this->verbose($record);
      $this->assertEqual((array)$record, $expected[$key]);
    }

    // Confirm that the node now loads new revision.
    $this->assertText('Test MRR Changed test node has been updated.');
    $this->assertText('Just a little changed test node.');
    // @todo Fix the cache clearing so this works.
    // Confirm that the new media object revision is now loaded.
    $this->assertText('Just a little changed test media item.');
    // Confirm the old media object revision is no longer loaded.
    $this->assertNoText('Just a little test media item.');

    // Clear the caches.
    drupal_flush_all_caches();

    // Reload the node and confirm the values are still correct.
    $this->drupalGet('node/1');
    $this->assertResponse(200);
    // Confirm that the node still loads new revision.
    $this->assertText('Changed test node');
    $this->assertText('Just a little changed test node.');
    // Confirm that the new media object revision is still loaded.
    $this->assertText('Just a little changed test media item.');
    // Confirm the old media object revision is no longer loaded.
    $this->assertNoText('Just a little test media item.');

    // Revert the display settings to just show the title link again.
    $this->changeMediaDisplaySettings(NULL, 'admin/structure/paragraphs_type/media_test/display');

    // Reload the node and confirm the values are still correct.
    $this->drupalGet('node/1');
    $this->assertResponse(200);
    // Confirm that the node still loads new revision.
    $this->assertText('Changed test node');
    $this->assertText('Just a little changed test node.');
    // Confirm that the new media object revision is still loaded.
    $this->assertText('Changed test media');
    // Confirm the old media object revision is no longer loaded.
    $this->assertNoText('Test media');

    // Delete the node, confirm that there are no more records.
    $this->drupalGet('node/1/delete');
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, [], 'Delete');
    $results = $this->getAllMrrRecords();
    $this->assertEqual(count($results), 0);
  }

}

<?php

namespace Drupal\Tests\media_reference_revisions\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Confirm the functionality works when using forward revisions.
 *
 * @group media_reference_revisions
 */
class ForwardRevisionsTest extends BrowserTestBase {

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

    // The secret ingredient for this test.
    'content_moderation',

    // Contrib requirements.
    'entity',
    'media_entity',

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
    $this->setupBasicEntities();

    // Update the node display settings.
    $this->changeMediaDisplaySettings('view');

    // Create the Content Moderation configuration.
    $this->enableContentModeration();
  }

  /**
   * Confirm the basic functionality.
   */
  public function testItWorks() {
    $expected = [
      [
        'id' => 1,
        'entity_type' => 'node',
        'entity_id' => 1,
        'entity_vid' => 1,
        'media_id' => 1,
        'media_vid' => 1,
      ],
      [
        'id' => 2,
        'entity_type' => 'node',
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

    // Create a published node with a reference to the media object.
    $this->drupalGet('node/add/test_mrr');
    $this->assertResponse(200);
    $edit = [
      'title[0][value]' => 'Test node',
      'body[0][value]' => 'Just a little test node.',
      'field_media[0][target_id]' => 'Test media (1)',
      'moderation_state[0][state]' => 'published',
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

    // Create a forward revision of the media object.
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
    $this->assertText('Just a little test media item.');
    // Confirm that the new media object revision isn't loaded.
    $this->assertNoText('Just a little changed test media item.');

    // Update the node, create a future revision.
    $this->drupalGet('node/1/edit');
    $this->assertResponse(200);
    $edit = [
      'title[0][value]' => 'Changed test node',
      'body[0][value]' => 'Just a little changed test node.',
      'revision' => TRUE,
      'revision_log[0][value]' => 'Testing creation of a new revision',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertResponse(200);

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
    // Confirm that the new media object revision is now loaded.
    $this->assertText('Just a little changed test media item.');
    // Confirm the old media object revision is no longer loaded.
    $this->assertNoText('Just a little test media item.');

    $this->drupalGet('node/1/revisions');
    $this->assertResponse(200);

    // Reload the original revision and confirm it shows the original values.
    $this->drupalGet('node/1');
    $this->assertResponse(200);
    // Confirm that the node still loads new revision.
    $this->assertText('Test node');
    $this->assertText('Just a little test node.');
    // Confirm that the new media object revision is not loaded.
    $this->assertNoText('Just a little changed test media item.');
    // Confirm the old media object revision is still loaded.
    $this->assertText('Just a little test media item.');

    // Reload the node's latest revision and confirm it shows the new values.
    $this->drupalGet('node/1/latest');
    $this->assertResponse(200);
    // Confirm that the node still loads new revision.
    $this->assertText('Changed test node');
    $this->assertText('Just a little changed test node.');
    // Confirm that the new media object revision is still loaded.
    $this->assertText('Just a little changed test media item.');
    // Confirm the old media object revision is no longer loaded.
    $this->assertNoText('Just a little test media item.');
  }

}

<?php

namespace Drupal\Tests\mrr_embed_filter\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media_reference_revisions\Functional\MediaReferenceRevisionsHelperTrait;

/**
 * Confirm how the WYSIWYG integration works when using Entity Embed.
 *
 * @group media_reference_revisions
 */
class WysiwygEmbedTest extends BrowserTestBase {

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
    'ckeditor',

    // Contrib requirements.
    'entity',
    'media_entity',

    // Needed for this test.
    'embed',
    'entity_embed',

    // This module.
    'media_reference_revisions',
    'mrr_embed_filter',
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

    // Set up the Entity Embed system.
    $this->setupEntityEmbed();
  }

  /**
   * Confirm the WYSIWYG functionality.
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
        'entity_vid' => 1,
        'media_id' => 2,
        'media_vid' => 2,
      ],
      [
        'id' => 3,
        'entity_type' => 'node',
        'entity_id' => 1,
        'entity_vid' => 2,
        'media_id' => 1,
        'media_vid' => 3,
      ],
      [
        'id' => 4,
        'entity_type' => 'node',
        'entity_id' => 1,
        'entity_vid' => 2,
        'media_id' => 2,
        'media_vid' => 2,
      ],
    ];

    // Create a media object.
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

    // Create a second media object.
    $this->drupalGet('media/add/test_media');
    $this->assertResponse(200);
    $edit = [
      'name[0][value]' => 'Another test media',
      'field_text[0][value]' => 'Just another little test media item.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save and publish');
    $this->assertResponse(200);
    $this->assertText('Test Media Another test media has been created.');
    $this->assertText('Just another little test media item.');

    // Load the media object's system path, to make sure the correct one is
    // loaded.
    $media_id1 = 1;
    $media_id2 = 2;
    $this->drupalGet('media/' . $media_id1);
    $this->assertResponse(200);
    $this->assertText('Test media');
    $this->assertText('Just a little test media item.');
    $this->drupalGet('media/' . $media_id2);
    $this->assertResponse(200);
    $this->assertText('Another test media');
    $this->assertText('Just another little test media item.');

    // Create the node with a reference to the media object.
    $this->drupalGet('node/add/test_mrr');
    $this->assertResponse(200);
    $edit = [
      'title[0][value]' => 'Test node',
      'body[0][value]' => '<drupal-entity data-entity-type="media" data-entity-id="' . $media_id1 . '" data-view-mode="teaser">This placeholder should not be rendered.</drupal-entity>'
        . '<drupal-entity data-entity-type="media" data-entity-id="' . $media_id2 . '" data-view-mode="teaser">This placeholder should not be rendered.</drupal-entity>',
      // 'body[0][format]' => 'custom_format',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertResponse(200);
    $this->assertText('Test MRR Test node has been created.');
    $this->assertNoRaw('<drupal-entity data-entity-type="node" data-entity');
    $this->assertRaw('<article class="embedded-entity">');
    $this->assertText('Just a little test media item');
    $this->assertText('Just another little test media item');

    // Load the node's system path, to make sure the correct one is loaded.
    $this->drupalGet('node/1');
    $this->assertResponse(200);
    $this->assertText('Test node');
    $this->assertText('Just a little test media item');
    $this->assertText('Just another little test media item');

    // Confirm that an MRR record was created for the above.
    $records = $this->getAllMrrRecords();

    // There should be one record for the above entities.
    $this->assertEqual(count($records), 2);
    foreach ($records as $key => $record) {
      $this->verbose($record);
      $this->assertEqual((array)$record, $expected[$key]);
    }

    // Create a new revision of the first media object.
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
    $records = $this->getAllMrrRecords();
    $this->assertEqual(count($records), 2);
    foreach ($records as $key => $record) {
      $this->verbose($record);
      $this->assertEqual((array)$record, $expected[$key]);
    }

    // Confirm that the node still loads the original media object revision.
    $this->drupalGet('node/1');
    $this->assertResponse(200);
    $this->assertText('Test node');
    // Confirm the old media object revision is loaded.
    $this->assertText('Just a little test media item');
    $this->assertText('Just another little test media item');
    // Confirm that the new media object revision isn't loaded.
    $this->assertNoText('Just a little changed test media item');

    // Clear the caches.
    drupal_flush_all_caches();

    // Confirm that the node still loads the original media object revision.
    $this->drupalGet('node/1');
    $this->assertResponse(200);
    $this->assertText('Test node');
    // Confirm the old media object revision is loaded.
    $this->assertText('Just a little test media item');
    $this->assertText('Just another little test media item');
    // Confirm that the new media object revision isn't loaded.
    $this->assertNoText('Just a little changed test media item');

    // Update the node.
    $this->drupalGet('node/1/edit');
    $this->assertResponse(200);
    $edit = [
      'title[0][value]' => 'Changed test node',
      'revision' => TRUE,
      'revision_log[0][value]' => 'Testing creation of a new revision',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertResponse(200);

    // This will be updated as the node was published.
    $expected[0]['media_vid'] = 3;

    // Verify that the MRR data is correct.
    $records = $this->getAllMrrRecords();
    $this->verbose($records);
    $this->assertEqual(count($records), 4);
    foreach ($records as $key => $record) {
      $this->verbose($record);
      $this->assertEqual((array)$record, $expected[$key]);
    }

    // Confirm that the node now loads new revision.
    $this->assertText('Test MRR Changed test node has been updated.');
    // Confirm that the new media object revision is now loaded.
    $this->assertText('Just a little changed test media item.');
    $this->assertText('Just another little test media item.');
    // Confirm the old media object revision is no longer loaded.
    $this->assertNoText('Just a little test media item.');

    // Clear the caches.
    drupal_flush_all_caches();

    // Reload the node and confirm the values are still correct.
    $this->drupalGet('node/1');
    $this->assertResponse(200);
    // Confirm that the node still loads new revision.
    $this->assertText('Changed test node');
    // Confirm that the new media object revision is still loaded.
    $this->assertText('Just a little changed test media item.');
    $this->assertText('Just another little test media item.');
    // Confirm the old media object revision is no longer loaded.
    $this->assertNoText('Just a little test media item.');
  }

  /**
   * Make sure the site doesn't blow up if a media object can't be loaded.
   */
  public function _testInvalidData() {
    // Create a node with a reference to a non-existant media object.
    $this->drupalGet('node/add/test_mrr');
    $this->assertResponse(200);
    $edit = [
      'title[0][value]' => 'Test node',
      // Point to a fictitious media entity #3.
      'body[0][value]' => '<p>Media entity #3 does not exist.</p><drupal-entity data-entity-type="media" data-entity-id="3" data-view-mode="teaser">This placeholder should not be rendered.</drupal-entity>',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertResponse(200);
    $this->assertText('Test MRR Test node has been created.');
    $this->assertRaw('<p>Media entity #3 does not exist.</p>');
  }

}

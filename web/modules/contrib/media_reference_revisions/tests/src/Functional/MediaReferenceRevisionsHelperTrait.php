<?php

namespace Drupal\Tests\media_reference_revisions\Functional;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\user\Entity\User;
use Drupal\embed\Entity\EmbedButton;

/**
 * Misc helper functions for the automated tests.
 */
trait MediaReferenceRevisionsHelperTrait {

  /**
   * Log in as user 1.
   */
  protected function loginUser1() {
    // Load user 1.
    /* @var \Drupal\user\Entity\User $account */
    $account = User::load(1);

    // Reset the password.
    $password = 'foo';
    $account->setPassword($password)->save();

    // Support old and new tests.
    $account->passRaw = $password;
    $account->pass_raw = $password;

    // Login.
    $this->drupalLogin($account);
  }

  /**
   * Create the necessary structures for the basic tests.
   */
  protected function setupBasicEntities() {
    // Create the media type.
    $this->createMediaType();

    // Create the content type.
    $this->createContentType();

    // Add a media field to the content type.
    $this->addMediaFieldToEntityBundle('admin/structure/types/manage/test_mrr/fields/add-field');
  }

  /**
   * Adjust the display of the media type.
   *
   * @param string $option
   *   The display option to use. Only four options are supported:
   *   - id - Shows just the entity's ID.
   *   - view - Shows a fully rendered entity.
   *   - label - Show's a link to the entity using its title.
   *   - thumbnail - Renders the entity using an image style.
   * @param string
   *   The path where the media field can be found on an entity bundle's display
   *   settings via Field UI.
   */
  protected function changeMediaDisplaySettings($option = 'label', $entity_display_admin_path = 'admin/structure/types/manage/test_mrr/display') {
    $this->drupalGet($entity_display_admin_path);
    $this->assertResponse(200);

    if ($option == 'id') {
      $edit = [
        'fields[field_media][type]' => 'entity_reference_entity_id',
      ];
    }
    elseif ($option == 'view') {
      $edit = [
        'fields[field_media][type]' => 'entity_reference_entity_view',
        // 'fields[field_media][settings_edit_form][settings][view_mode]' => 'default',
      ];
    }
    elseif ($option == 'label') {
      $edit = [
        'fields[field_media][type]' => 'entity_reference_label',
        // 'fields[field_media][settings_edit_form][settings][link]' => TRUE,
      ];
    }
    elseif ($option == 'thumbnail') {
      $edit = [
        'fields[field_media][type]' => 'media_thumbnail',
        // 'fields[field_media][settings_edit_form][settings][image_style]' => 'medium',
        // 'fields[field_media][settings_edit_form][settings][image_link]' => 'media',
      ];
    }
    else {
      return;
    }
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertResponse(200);
    $this->assertText('Your settings have been saved.');
  }

  /**
   * Enable content moderation for the test content type.
   */
  protected function enableContentModeration() {
    $this->drupalGet('admin/config/workflow/workflows/manage/editorial/type/node');
    $this->assertResponse(200);
    $edit = [
      'bundles[test_mrr]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
  }

  /**
   * {@inheritdoc}
   */
  protected function verbose($message, $title = NULL) {
    // Handle arrays, objects, etc.
    if (!is_string($message)) {
      $message = "<pre>\n" . print_r($message, TRUE) . "\n</pre>\n";
    }

    // Optional title to go before the output.
    if (!empty($title)) {
      $title = '<h2>' . Html::escape($title) . "</h2>\n";
    }

    parent::verbose($title . $message);
  }

  /**
   * Get all of the MRR records.
   *
   * @return array
   *   All of the records from the {media_reference_revision} table.
   */
  protected function getAllMrrRecords() {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = \Drupal::database()
      ->select('media_reference_revision', 'mrr')
      ->fields('mrr')
      ->orderBy('id');
    return $query->execute()->fetchAll();
  }

  /**
   * Enable Entity Embed, etc.
   */
  protected function setupEntityEmbed() {
    // Create a new entity button.
    $button = EmbedButton::create([
      'langcode' => 'en',
      'status' => '1',
      'label' => 'Testing',
      'id' => 'testing',
      'type_id' => 'entity',
      'type_settings' => [
        'entity_type' => 'media',
        'bundles' => [
          'test_media',
        ],
        'display_plugins' => [],
      ],
      'icon_uuid' => NULL,
      'dependencies' => [
        'config' => [
          'media_entity.bundle.test_media',
        ],
        'module' => [
          'entity_embed',
          'media_entity',
        ],
      ],
    ]);
    $button->save();

    // Create a text format and enable the entity_embed filter.
    $format = FilterFormat::create([
      'format' => 'custom_format',
      'name' => 'Custom format',
      'filters' => [
        'entity_embed_revision' => [
          'status' => 1,
        ],
      ],
    ]);
    $format->save();
    $editor_group = [
      'name' => 'Entity Embed',
      'items' => [
        $button->id(),
      ],
    ];
    $editor = Editor::create([
      'format' => 'custom_format',
      'editor' => 'ckeditor',
      'settings' => [
        'toolbar' => [
          'rows' => [[$editor_group]],
        ],
      ],
    ]);
    $editor->save();
  }

  /**
   * Create a media type for use elsewhere.
   */
  protected function createMediaType() {
    // Create the media bundle.
    $this->drupalGet('admin/structure/media/add');
    $this->assertResponse(200);
    $edit = [
      'label' => 'Test Media',
      'id' => 'test_media',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save media bundle');
    $this->assertResponse(200);
    $this->assertText('The media bundle Test Media has been added.');

    // Add a field to the media bundle.
    $this->drupalGet('admin/structure/media/manage/test_media/fields/add-field');
    $this->assertResponse(200);
    $edit = [
      'new_storage_type' => 'string',
      'label' => 'Text',
      'field_name' => 'text',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save and continue');
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, [], 'Save field settings');
    $this->assertResponse(200);
    $this->assertText('Updated field Text field settings.');
    $this->drupalPostForm(NULL, [], 'Save settings');
    $this->assertResponse(200);
    $this->assertText('Saved Text configuration.');
  }

  /**
   * Create a content type for testing.
   */
  protected function createContentType(array $values = []) {
    $this->drupalGet('admin/structure/types/add');
    $this->assertResponse(200);
    $edit = [
      'name' => 'Test MRR',
      'type' => 'test_mrr',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save and manage fields');
    $this->assertResponse(200);
    $this->assertText('The content type Test MRR has been added.');
  }

  /**
   * Create the necessary structures for the paragraph tests.
   */
  protected function setupParagraphEntities() {
    $this->createMediaType();
    $this->createContentType();

    // Add a Paragraphs type
    $this->drupalGet('admin/structure/paragraphs_type/add');
    $this->assertResponse(200);
    $edit = [
      'label' => 'Media test',
      'id' => 'media_test',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save and manage fields');
    $this->assertResponse(200);

    // Add a media field to the paragraph type.
    $this->addMediaFieldToEntityBundle('admin/structure/paragraphs_type/media_test/fields/add-field');

    // Add a Paragraphs field to the content type.
    $this->drupalGet('admin/structure/types/manage/test_mrr/fields/add-field');
    $this->assertResponse(200);
    $edit = [
      'new_storage_type' => 'field_ui:entity_reference_revisions:paragraph',
      'label' => 'Paragraphs',
      'field_name' => 'paragraphs',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save and continue');
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, [], 'Save field settings');
    $this->assertResponse(200);
    $this->assertText('Updated field Paragraphs field settings.');
    $edit = [
      'required' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save settings');
    $this->assertText('Saved Paragraphs configuration.');
  }

  /**
   * Add a media field to an entity bundle.
   *
   * @param string $entity_add_field_path
   *   The full system path to the Field UI admin page for adding fields to an
   *   entity bundle.
   */
  protected function addMediaFieldToEntityBundle($entity_add_field_path) {
    $this->drupalGet($entity_add_field_path);
    $this->assertResponse(200);
    $edit = [
      'new_storage_type' => 'entity_reference',
      'label' => 'Media',
      'field_name' => 'media',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save and continue');
    $this->assertResponse(200);
    $edit = [
      'cardinality' => -1,
      'settings[target_type]' => 'media',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save field settings');
    $this->assertResponse(200);
    $this->assertText('Updated field Media field settings.');
    $edit = [
      'settings[handler_settings][target_bundles][test_media]' => 'test_media',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save settings');
    $this->assertText('Saved Media configuration.');
  }
}

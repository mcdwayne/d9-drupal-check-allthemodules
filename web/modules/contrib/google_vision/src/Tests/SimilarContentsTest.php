<?php

namespace Drupal\google_vision\Tests;

use Drupal\Core\Url;

/**
 * Tests whether the similar contents are displayed for the image files or not.
 *
 * @group google_vision
 */
class SimilarContentsTest extends GoogleVisionTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'file',
    'taxonomy',
    'entity_reference',
  ];

  /**
   * A user with permission to create image files.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    //Creates an administrator user.
    $this->adminUser = $this->drupalCreateUser([
      'administer google vision',
      'administer files',
      'edit any image files',
      'create files',
      'administer file types',
      'administer file fields',
      'administer taxonomy',
    ]);
    $this->drupalLogin($this->adminUser);
    // Check whether the API key is set.
    $this->drupalGet(Url::fromRoute('google_vision.settings'));
    $this->assertNotNull('api_key', 'The api key is set');
  }

  /**
   * Test to ensure that the Similar Contents contains the related images.
   */
  public function testSimilarContents() {
    $name = 'Dominant Color';
    $vid = 'dominant_color';
    $count = 0;
    // Create a taxonomy vocabulary.
    $vocabulary = $this->createTaxonomyVocabulary($name, $vid);
    // Check whether the vocabulary is created.
    $this->drupalGet(Url::fromRoute('entity.taxonomy_vocabulary.collection'));
    $this->assertResponse(200);
    // Create a taxonomy reference field.
    $this->createEntityReferenceField($vocabulary);
    $edit = [
      'google_vision' => 1,
      'settings[handler_settings][auto_create]' => 1,
    ];
    $this->drupalPostForm('admin/structure/file-types/manage/image/edit/fields/file.image.field_labels', $edit, t('Save settings'));
    // Ensure that the Dominant Color option is selected.
    $this->drupalGet('admin/structure/file-types/manage/image/edit/fields/file.image.field_labels');
    // Upload an image file.
    $file_id = $this->uploadImageFile($count);

    // Create multiple images to be displayed in the similar contents link.
    for ($count = 1; $count < 3; $count++) {
      $id[$count] = $this->uploadImageFile($count);
      $this->assertNotNull($id[$count], 'The image file is created');
    }
    // Display the similar contents together.
    $this->drupalGet('file/' . $file_id . '/similarcontent');
    $this->assertResponse(200);
    $this->assertNoText('No items found.', 'Similar Contents are displayed.');
  }

  /**
   * Test to ensure that no similar images are present.
   */
  public function testNoSimilarContents() {
    $name = 'Labels';
    $vid = 'other_labels';
    $count = 0;
    // Create a taxonomy vocabulary.
    $vocabulary = $this->createTaxonomyVocabulary($name, $vid);
    // Check whether the vocabulary is created.
    $this->drupalGet(Url::fromRoute('entity.taxonomy_vocabulary.collection'));
    $this->assertResponse(200);
    // Create a taxonomy reference field.
    $this->createEntityReferenceField($vocabulary);
    $edit = [
      'google_vision' => 1,
      'settings[handler_settings][auto_create]' => 1,
    ];
    $this->drupalPostForm('admin/structure/file-types/manage/image/edit/fields/file.image.field_labels', $edit, t('Save settings'));
    // Ensure that the Dominant Color option is not selected.
    $this->drupalGet('admin/structure/file-types/manage/image/edit/fields/file.image.field_labels');
    // Upload an image file.
    $file_id = $this->uploadImageFile($count);

    // Create multiple images to be displayed in the similar contents link.
    for ($count = 1; $count < 3; $count++) {
      $id[$count] = $this->uploadImageFile($count);
      $this->assertNotNull($id[$count], 'The image file is created');
    }
    $this->drupalGet('file/' . $file_id . '/similarcontent');
    $this->assertResponse(200);
    // Assert that no similar items are available.
    $this->assertText('No items found.', 'No items found message is displayed.');
  }
}

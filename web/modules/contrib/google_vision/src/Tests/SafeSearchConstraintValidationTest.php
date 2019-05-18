<?php

namespace Drupal\google_vision\Tests;

use Drupal\Core\Url;

/**
 * Tests to verify whether the Safe Search Constraint Validation works
 * correctly.
 *
 * @group google_vision
 */
class SafeSearchConstraintValidationTest extends GoogleVisionTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'comment',
  ];

  /**
   * A user with permission to create content and upload images.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create custom content type.
    $this->drupalCreateContentType([
      'type' => 'test_images',
      'name' => 'Test Images'
    ]);
    // Creates administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer google vision',
      'create test_images content',
      'access content',
      'access administration pages',
      'administer node fields',
      'administer nodes',
      'administer node display',
      'administer comments',
      'administer comment types',
      'administer comment fields',
      'administer comment display',
      'access comments',
      'post comments',
    ]);
    $this->drupalLogin($this->adminUser);
    // Check whether the api key is set.
    $this->drupalGet(Url::fromRoute('google_vision.settings'));
    $this->assertNotNull('api_key', 'The api key is set');
  }

  /**
   * Test to ensure explicit content is detected when Safe Search is enabled.
   */
  public function testSafeSearchConstraintForNodes() {
    // Get the image field id.
    $field_id = $this->getImageFieldId('node', 'test_images');

    // Enable the Safe Search.
    $edit = [
      'safe_search' => 1,
    ];
    $this->drupalPostForm("admin/structure/types/manage/test_images/fields/$field_id", $edit, t('Save settings'));

    // Ensure that the safe search is enabled.
    $this->drupalGet("admin/structure/types/manage/test_images/fields/$field_id");

    // Save the node.
    $node_id = $this->createNodeWithImage();

    // Assert the constraint message.
    $this->assertText('This image contains explicit content and will not be saved.', 'Constraint message found');
    // Assert that the node is not saved.
    $this->assertFalse($node_id, 'The node has not been saved');
  }

  /**
   * Test to ensure no explicit content is detected when Safe Search is disabled.
   */
  public function testNoSafeSearchConstraintForNodes() {
    // Get the image field id.
    $field_id = $this->getImageFieldId('node', 'test_images');

    // Ensure that the safe search is disabled.
    $this->drupalGet("admin/structure/types/manage/test_images/fields/$field_id");

    // Save the node.
    $node_id = $this->createNodeWithImage();

    // Assert that no constraint message appears.
    $this->assertNoText('This image contains explicit content and will not be saved.', 'No Constraint message found');
    // Display the node.
    $this->drupalGet('node/' . $node_id);
  }

  /**
   * Test to ensure that explicit content is detected in comments when Safe Search enabled.
   */
  public function testSafeSearchConstraintForComments() {
    // Creating a new comment type.
    $type = $this->createCommentType('test_comment');

    // Ensure that the comment type is created and we get proper response.
    $this->drupalGet('admin/structure/comment/manage/' . $type->id());
    $this->assertResponse(200);

    // Get the field id of the image field added to the comment type.
    $field_id = $this->getImageFieldId('comment', $type->id());
    $this->drupalGet('admin/structure/comment/manage/' . $type->id() . '/fields');

    // Add the comment field to test_images.
    $this->addCommentField('node', 'test_images');

    $this->drupalGet("admin/structure/types/manage/test_images/fields");
    // Enable the safe search detection feature.
    $edit = [
      'safe_search' => 1,
    ];
    $this->drupalPostForm("admin/structure/comment/manage/test_comment/fields/$field_id", $edit, t('Save settings'));

    // Ensure that Safe Search is on.
    $this->drupalGet("admin/structure/comment/manage/test_comment/fields/$field_id");

    // Create and save a node.
    $this->getImageFieldId('node', 'test_images');
    $node_id = $this->createNodeWithImage();

    // Add a comment to the node.
    $this->createCommentWithImage($node_id);

    // Assert the constraint message.
    $this->assertText('This image contains explicit content and will not be saved.', 'Constraint message found');
  }

  /**
   * Test to ensure that explicit content is detected in comments when Safe Search disabled.
   */
  public function testNoSafeSearchConstraintForComments() {
    // Creating a new comment type.
    $type = $this->createCommentType('test_comment');

    // Ensure that the comment type is created and we get proper response.
    $this->drupalGet('admin/structure/comment/manage/' . $type->id());
    $this->assertResponse(200);

    // Get the field id of the image field added to the comment type.
    $field_id = $this->getImageFieldId('comment', $type->id());
    $this->drupalGet('admin/structure/comment/manage/' . $type->id() . '/fields');

    // Add the comment field to test_images.
    $this->addCommentField('node', 'test_images');
    $this->drupalGet("admin/structure/comment/manage/test_comment/fields/$field_id");

    // Create and save a node.
    $this->getImageFieldId('node', 'test_images');
    $node_id = $this->createNodeWithImage();

    // Add a comment to the node.
    $this->createCommentWithImage($node_id);

    // Assert the constraint message.
    $this->assertNoText('This image contains explicit content and will not be saved.', 'No Constraint message found');
    $this->drupalGet('node/' . $node_id);
  }
}

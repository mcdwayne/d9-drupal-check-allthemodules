<?php

namespace Drupal\Tests\comment_alter\Functional;

use Drupal\Tests\comment_alter\Functional\CommentAlterTestBase;

/**
 * Tests the comment alter module functions for image fields.
 *
 * @group comment_alter
 */
class CommentAlterImageTest extends CommentAlterTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['image'];

  /**
   * Adds an image field to the parent enity.
   *
   * @param int $cardinality
   *   Cardinality of the field.
   *
   * @return string
   *   The name of the field which was created.
   */
  protected function addImageField($cardinality) {
    return $this->addField('image', 'image_image', [
      'cardinality' => $cardinality,
    ]);
  }

  /**
   * Gets a list of images to be used in the test.
   *
   * @return array $images
   *   The file objects representing the image files.
   */
  protected function getImageFiles() {
    $original = drupal_get_path('module', 'simpletest') . '/files';
    $images = file_scan_directory($original, '/image-.*/');
    foreach ($images as $image) {
      file_unmanaged_copy($image->uri, \Drupal\Core\StreamWrapper\PublicStream::basePath());
    }

    return $images;
  }

  /**
   * Posts a comment with image using the psuedo browser.
   *
   * @param string $field_name
   *   Name of the image field. This field comes from the parent entity where it
   *   is comment alterable.
   * @param array $img_field
   *   The image field value which is to be uploaded.
   * @param int $field_number
   *   (optional) The field number for multi-valued image field.
   */
  protected function postCommentWithImage($field_name, $img_field, $field_number = 0) {
    // Upload the image first.
    $this->drupalGet('comment/reply/' . $this->entityType . '/' . $this->entity->id() . '/comment');
    $this->drupalPostForm(NULL, $img_field, t('Upload'));

    // Now fill other fields including the alt field of the image in the comment
    // form and save it to post a comment.
    $edit['comment_alter_fields[' . $field_name . '][' . $field_number . '][alt]'] = $this->randomString();
    $edit['comment_body[0][value]'] = $this->randomString();
    $edit['subject[0][value]'] = $this->randomString();
    $this->drupalPostForm(NULL, $edit, t('Save'));
  }

  /**
   * Tests for single valued image field comment altering.
   */
  public function testImageFieldSingle() {
    $field_name = $this->addImageField(1);
    $this->createEntityObject();

    $image = current($this->getImageFiles());
    $this->postCommentWithImage($field_name, ['files[comment_alter_fields_' . $field_name . '_0]' => drupal_realpath($image->uri)]);

    $this->assertCommentDiff([
      $field_name => [
        [NULL, 'Image: ' . $image->filename],
        [NULL, 'File ID: 1'],
      ],
    ]);
    $this->assertRevisionDelete();
  }

  /**
   * Tests for multi valued image field comment altering.
   */
  public function testImageFieldMultiple() {
    $field_name = $this->addImageField(-1);
    $images = $this->getImageFiles();
    $image1 = current($this->getImageFiles());
    $image2 = end($images);
    // Create an entity object without the image.
    $this->createEntityObject();
    // Now edit the entity to add an image to the image field.
    $this->drupalGet('entity_test_rev/manage/' . $this->entity->id() . '/edit');
    $img['files[' . $field_name . '_0][]'] = drupal_realpath($image1->uri);
    $this->drupalPostForm(NULL, $img, t('Upload'));
    $edit[$field_name . '[0][alt]'] = $this->randomString();
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->postCommentWithImage($field_name, ['files[comment_alter_fields_' . $field_name . '_1][]' => drupal_realpath($image2->uri)], 1);

    $this->assertCommentDiff([
      $field_name => [
        ['File ID: 1', 'File ID: 1'],
        [NULL, 'Image: ' . $image2->filename],
        [NULL, 'File ID: 2'],
      ],
    ]);
    $this->assertRevisionDelete();
  }

}

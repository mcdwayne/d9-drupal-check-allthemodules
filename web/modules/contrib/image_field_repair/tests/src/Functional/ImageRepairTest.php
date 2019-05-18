<?php

namespace Drupal\Tests\image_field_repair\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\image\Functional\ImageFieldTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests image repair process.
 *
 * @group image_field_repair
 */
class ImageRepairTest extends ImageFieldTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['image_field_repair'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $storage_settings = ['cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED];
    $field_settings = ['alt_field_required' => 0];
    $this->createImageField('image1', 'article', $storage_settings, $field_settings);
    $this->createImageField('image2_empty', 'article', $storage_settings, $field_settings);
    $this->createImageField('image3', 'article', $storage_settings, $field_settings);

    $image = $this->getTestFiles('image')[0];
    $path_to_image = \Drupal::service('file_system')->realpath($image->uri);
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'files[image1_0][]' => $path_to_image,
      'files[image3_0][]' => $path_to_image,
    ];
    $this->drupalPostForm('node/add/article', $edit, t('Save'));

    // Corrupted width/height values for some fields.
    \Drupal::database()
      ->update('node__image1')
      ->fields([
        'image1_width' => 0,
        'image1_height' => 0,
      ])
      ->execute();
  }

  /**
   * Tests repair process.
   */
  public function testEmptyTable() {
    $this->drupalPostForm('/admin/config/media/image_file_repair/dimensions', [], 'Start');
    $result = $this->getSession()->getPage()->getText();
    $this->assertContains('Repaired 1 out of 1 records in table "node__image1" for image field "node.image1".', $result);
    $this->assertNotContains('Repaired 0 out of 1 records in table "node__image3" for image field "node.image3".', $result);
    $this->assertContains('Total: Repaired 1 out of 4 checked records in 3 image fields.', $result);
  }

}

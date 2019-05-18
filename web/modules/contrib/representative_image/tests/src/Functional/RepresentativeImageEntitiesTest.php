<?php

namespace Drupal\Tests\representative_image\Functional;

/**
 * Test that entities can have associated representative image fields.
 *
 * @group representative_image
 */
class RepresentativeImageEntitiesTest extends RepresentativeImageBaseTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field_ui', 'file', 'image', 'node', 'representative_image'];

  /**
   * Confirm that the defaults are sensible out of the box.
   */
  public function testDefaults() {
    $image1 = $this->randomFile('image');
    $image2 = $this->randomFile('image');

    // 1. Set the first image field as the representative.
    $edit = [
      'settings[representative_image_field_name]' => 'field_image1',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.field_representative_image', $edit, 'Save settings');

    // Create a node with an image in field_image1. Check that it is shown.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'files[field_image1_0]' => $this->fileSystem->realpath($image1->uri),
    ];
    $this->drupalPostForm('node/add/article', $edit, 'Save');
    $this->drupalPostForm(NULL, ['field_image1[0][alt]' => $this->randomMachineName()], t('Save'));
    $this->assertSession()->responseContains($image1->name);

    // 2. Set representative image to fall back to the first available image found in the entity.
    $edit = [
      'settings[representative_image_behavior]' => 'first',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.field_representative_image', $edit, 'Save settings');

    // Create a node with an image in the second image field. Check that it is shown.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'files[field_image2_0]' => $this->fileSystem->realpath($image2->uri),
    ];
    $this->drupalPostForm('node/add/article', $edit, 'Save');
    $this->drupalPostForm(NULL, ['field_image2[0][alt]' => $this->randomMachineName()], t('Save'));
    $this->assertSession()->responseContains($image2->name);

    // 3. Set the fallback to first image found or default image.
    $edit = [
      'settings[representative_image_behavior]' => 'first_or_default',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.field_representative_image', $edit, 'Save settings');

    // Create a node without images.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
    ];
    $this->drupalPostForm('node/add/article', $edit, 'Save');
    $this->assertSession()->responseContains($this->defaultImageFile->name);
  }

}

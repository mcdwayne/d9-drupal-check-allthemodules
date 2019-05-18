<?php

namespace Drupal\Tests\representative_image\Functional;

/**
 * Test the token integration of representative images.
 *
 * @group representative_image
 */
class RepresentativeImageTokenTest extends RepresentativeImageBaseTest {

  /**
   * Tests custom tokens.
   *
   * Test that the custom tokens defined by representative image return the
   * expected values.
   */
  public function testTokenIntegration() {
    /** @var \Drupal\Core\Utility\Token $token */
    $token = \Drupal::token();

    $image1 = $this->randomFile('image');
    $image2 = $this->randomFile('image');

    // Create an article node for testing.
    $edit = [
      'title[0][value]' => $this->randomString(),
      'files[field_image1_0]' => $this->fileSystem->realpath($image1->uri),
      'files[field_image2_0]' => $this->fileSystem->realpath($image2->uri),
    ];
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $this->drupalPostForm(NULL, [
      'field_image1[0][alt]' => $this->randomMachineName(),
      'field_image2[0][alt]' => $this->randomMachineName(),
    ], 'Save');

    // Confirm that the correct image is being replaced properly.
    // 1. Set the first image field as the representative.
    $edit = [
      'settings[representative_image_field_name]' => 'field_image1',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.field_representative_image', $edit, 'Save settings');
    drupal_flush_all_caches();

    // Check that the first image is shown in a processed token.
    $node = $this->nodeStorage->load(1);
    $replacement = $token->replace("foo [node:representative_image] bar", ['node' => $node]);
    $this->assertTrue(strpos($replacement, $image1->name) !== FALSE);

    // 2. Switch the representative image and confirm the representative image is
    // being replaced properly.
    $edit = [
      'settings[representative_image_field_name]' => 'field_image2',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.field_representative_image', $edit, 'Save settings');
    // @TODO Need to do this or RepresentativeImagePicker::getRepresentativeImageField() will return ''.
    drupal_flush_all_caches();
    $this->nodeStorage->resetCache([1]);

    // Check that the second image is shown in a processed token.
    $node = $this->nodeStorage->load(1);
    $replacement = $token->replace("foo [node:representative_image] bar", ['node' => $node]);
    $this->assertTrue(strpos($replacement, $image2->name) !== FALSE);
  }

}

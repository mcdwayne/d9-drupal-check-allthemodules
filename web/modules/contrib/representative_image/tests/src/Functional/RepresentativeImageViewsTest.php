<?php

namespace Drupal\Tests\representative_image\Functional;

/**
 * Test the views integration of representative images.
 *
 * @group representative_image
 */
class RepresentativeImageViewsTest extends RepresentativeImageBaseTest {

  /**
   * Creates a view with two content types sharing a representative image field.
   */
  public function testViewsIntegration() {
    // Create a second content type.
    $this->drupalCreateContentType(['type' => 'article2', 'name' => 'Article2']);
    $this->createImageField('field_image3', 'article2');
    $this->createImageField('field_image4', 'article2');

    // Add the existing representative image field. Set it to field3.
    $this->drupalGet('admin/structure/types/manage/article2/fields/add-field');
    $this->getSession()->getPage()
      ->findField('existing_storage_name')
      ->setValue('field_representative_image');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->pressButton('Save and continue');
    $edit = [
      'settings[representative_image_field_name]' => 'field_image3',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save settings');
    drupal_flush_all_caches();

    // Create image files to use for testing.
    $image1 = $this->randomFile('image');
    $image2 = $this->randomFile('image');
    $image3 = $this->randomFile('image');
    $image4 = $this->randomFile('image');

    // Create a node of each content type.
    $edit = [
      'title[0][value]' => $this->randomString(),
      'files[field_image1_0]' => $this->fileSystem->realpath($image1->uri),
      'files[field_image2_0]' => $this->fileSystem->realpath($image2->uri),
    ];
    $this->drupalPostForm('node/add/article', $edit, 'Save');
    $this->drupalPostForm(NULL, [
      'field_image1[0][alt]' => $this->randomMachineName(),
      'field_image2[0][alt]' => $this->randomMachineName(),
    ], 'Save');

    $edit = [
      'title[0][value]' => $this->randomString(),
      'files[field_image3_0]' => $this->fileSystem->realpath($image3->uri),
      'files[field_image4_0]' => $this->fileSystem->realpath($image4->uri),
    ];
    $this->drupalPostForm('node/add/article2', $edit, 'Save');
    $this->drupalPostForm(NULL, [
      'field_image3[0][alt]' => $this->randomMachineName(),
      'field_image4[0][alt]' => $this->randomMachineName(),
    ], 'Save');

    // Create a view that lists all content using fields.
    $this->drupalGet('admin/structure/views/add');
    $page = $this->getSession()->getPage();
    $page->findField('edit-label')->setValue('Sample view');
    $page->findField('edit-page-create')->check();
    $page = $this->getSession()->getPage();
    $page->findField('edit-page-style-row-plugin')->setValue('fields');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->pressButton('Save and edit');
    $page->findField('id')->setValue('sample_view');
    $page->pressButton('Save and edit');

    // Add the representative image field to the view.
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '#views-add-field'));
    $this->click('#views-add-field');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->findField('override[controls][options_search]')->setValue('representative');
    $this->getSession()->getPage()->findField('name[node__field_representative_image.field_representative_image]')->check();
    // The Save and continue button has a weird structure.
    $this->getSession()->getPage()->find('css', 'div > button.button.button--primary.js-form-submit.form-submit.ui-button.ui-corner-all.ui-widget')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Set the representative image's image style and link properties.
    $this->getSession()->getPage()->findField('options[settings][image_style]')->setValue('medium');
    $this->getSession()->getPage()->findField('options[settings][image_link]')->setValue('content');
    // The Apply button has a weird structure.
    $this->getSession()->getPage()->find('css', 'div > button.button.button--primary.js-form-submit.form-submit.ui-button.ui-corner-all.ui-widget')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Save the view.
    $this->getSession()->getPage()->pressButton('edit-actions-submit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Open the view and check that the right fields are being shown.
    $this->drupalGet('sample-view');
    $this->assertImage($image1);
    $this->assertImage($image3);

    // 2. Switch the representative image and confirm the representative image is
    // being replaced properly.
    $edit = [
      'settings[representative_image_field_name]' => 'field_image2',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.field_representative_image', $edit, 'Save settings');
    // 2. Set the second image field of Sample content type 2 as representative.
    $edit = [
      'settings[representative_image_field_name]' => 'field_image4',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article2/fields/node.article2.field_representative_image', $edit, 'Save settings');
    // @TODO Check why clearing caches is needed in order to find field_representative_image in the node.
    drupal_flush_all_caches();

    // Open the view and check that the right fields are being shown.
    $this->drupalGet('sample-view');
    $this->assertImage($image2);
    $this->assertImage($image4);
  }

}

<?php

namespace Drupal\Tests\nexx_integration\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\Tests\nexx_integration\FunctionalJavascript\NexxTestTrait;

/**
 * Test Nexx integration.
 *
 * @group nexx_integration
 */
class NexxIntegrationVideoTest extends BrowserTestBase {

  use FieldUiTestTrait;
  use NexxTestTrait;

  public static $modules = [
    'taxonomy',
    'nexx_integration',
    'nexx_integration_test',
    'field_ui',
    'field',
  ];

  /**
   * Test the endpoint.
   */
  public function testVideoEndpoint() {
    $data = $this->getTestVideoData(1);

    // Test connectivity.
    $videoData = $this->postVideoData($data);
    $this->assertEquals($data->itemData->general->ID, $videoData->refnr);
  }

  /**
   * Test the created video entity.
   */
  public function testBasicVideoCreation() {
    $data = $this->getTestVideoData(2);
    $videoData = $this->postVideoData($data);

    $videoEntity = $this->loadVideoEntity($videoData->value);
    $videoFieldName = $this->videoManager->videoFieldName();
    $videoField = $videoEntity->get($videoFieldName);

    $this->assertEquals($videoEntity->label(), $videoField->title);

    $this->assertEquals($data->itemData->general->ID, $videoField->item_id);
    $this->assertEquals($data->itemData->general->title, $videoField->title);
    $this->assertEquals($data->itemData->general->hash, $videoField->hash);
    $this->assertEquals($data->itemData->general->teaser, $videoField->teaser);
    $this->assertEquals($data->itemData->general->copyright, $videoField->copyright);
    $this->assertEquals($data->itemData->general->runtime, $videoField->runtime);
    $this->assertEquals($data->itemData->publishingdata->allowedOnDesktop, $videoField->isSSC);
    $this->assertEquals($data->itemData->publishingdata->validFromDesktop, $videoField->validfrom_ssc);
    $this->assertEquals($data->itemData->publishingdata->validUntilDesktop, $videoField->validto_ssc);
    $this->assertEquals($data->itemData->publishingdata->allowedOnMobile, $videoField->isMOBILE);
    $this->assertEquals($data->itemData->publishingdata->validFromMobile, $videoField->validfrom_mobile);
    $this->assertEquals($data->itemData->publishingdata->validUntilMobile, $videoField->validto_mobile);
    $this->assertEquals($data->itemData->publishingdata->isPublished, $videoField->active);
    $this->assertEquals($data->itemData->publishingdata->isDeleted, $videoField->isDeleted);
    $this->assertEquals($data->itemData->publishingdata->isBlocked, $videoField->isBlocked);
  }

  /**
   * Test the video entity 3: test active=0 during create.
   */
  public function testInactiveVideoCreation() {
    $id = 3;
    // Send active=0 now.
    $data = $this->getTestVideoData($id);
    $data->itemData->publishingdata->isPublished = 0;

    $videoData = $this->postVideoData($data);
    $this->assertEquals($videoData->refnr, $id, "Video id is $id");

    $videoEntity = $this->loadVideoEntity($videoData->value);
    $this->assertEquals($videoEntity->get("status")->getString(), 0, "Video id $id should be status=0 because of active=0");

  }

  /**
   * Test the video entity 4: test active=0 during update.
   */
  public function testInactiveVideoUpdate() {
    $id = 4;

    $data = $this->getTestVideoData($id);
    $videoData = $this->postVideoData($data);

    $videoEntity = $this->loadVideoEntity($videoData->value);
    $this->assertEquals(1, $videoEntity->get("status")->getString(), 'Video id
    $id should be status=1 because of active=1'
    );

    // Send active=0 now.
    $data->itemData->publishingdata->isPublished = 0;

    $videoData = $this->postVideoData($data);

    $videoEntity = $this->loadVideoEntity($videoData->value);
    $this->assertEquals(0, $videoEntity->get("status")->getString(), "Video id
    $id should be status=0 because of active=0"
    );
  }

  /**
   * Test the video entity 5: test isSSC=0 during create.
   */
  public function testInactiveSscVideoCreation() {
    $id = 5;

    $data = $this->getTestVideoData($id);
    // Send isSSC=0 now.
    $data->itemData->publishingdata->allowedOnDesktop = 0;

    $videoData = $this->postVideoData($data);
    $this->assertEquals($videoData->refnr, $id, "Video id is $id");

    $videoEntity = $this->loadVideoEntity($videoData->value);
    $this->assertEquals($videoEntity->get("status")->getString(), 0, "Video id $id should be status=1 because of isSSC=0.");
  }

  /**
   * Test the video entity 6: test isSSC=0 during update.
   */
  public function testInactiveSscVideoUpdate() {
    $id = 6;
    $data = $this->getTestVideoData($id);
    $videoData = $this->postVideoData($data);

    $videoEntity = $this->loadVideoEntity($videoData->value);
    $this->assertEquals($videoEntity->get("status")->getString(), 1, "Video id
    $id should be status=1 because of isSSC=1.");

    // Send isSSC=0 now.
    $data->itemData->publishingdata->allowedOnDesktop = 0;
    $videoData = $this->postVideoData($data);

    $videoEntity = $this->loadVideoEntity($videoData->value);
    $this->assertEquals($videoEntity->get("status")->getString(), 0, "Video id
    $id should be status=0 because of isSSC=0.");
  }

  /**
   * Test the video entity 7: test deleted=1 during create.
   */
  public function testDeletedVideoCreate() {
    $id = 7;
    $count = $this->countVideos();
    $data = $this->getTestVideoData($id);
    // Send delete=1 now.
    $data->itemData->publishingdata->isDeleted = 1;

    $videoData = $this->postVideoData($data);
    $this->assertEquals($videoData->refnr, $id, "Video id is $id");

    $this->assertNull($videoData->value, "Response value should be NULL for video id $id, video was not created because it is deleted=1.");
    $this->assertEquals($count, $this->countVideos(), "Counting all videos. Video id $id should not be created because it is deleted=1.");
  }

  /**
   * Test the video entity 8: test deleted=1 during update.
   */
  public function testDeletedVideoUpdate() {
    $id = 8;

    // Send delete=0 now.
    $data = $this->getTestVideoData($id);
    $videoData = $this->postVideoData($data);

    $videoEntity = $this->loadVideoEntity($videoData->value);
    $this->assertEquals($videoEntity->get("status")->getString(), 1, "Video id
    $id should be status=1 before deletion.");
    $count = $this->countVideos();

    $data = $this->getTestVideoData($id);
    // Send delete=1 now.
    $data->itemData->publishingdata->isDeleted = 1;

    $videoData = $this->postVideoData($data);

    $videoEntity = $this->loadVideoEntity($videoData->value);
    $this->assertNull($videoEntity, "Video id $id should be deleted.");
    $this->assertEquals($count - 1, $this->countVideos(), "Counting all videos 
    after deletion. Video id $id should be deleted.");
  }

  /**
   * Test expiration cron.
   */
  public function testCronExpiration() {
    $id = 9;
    $pastDate = REQUEST_TIME - 10000;
    $futureDate = REQUEST_TIME + 10000;
    $videoFieldName = $this->videoManager->videoFieldName();

    // First create a new entity that should be created as an active entity
    // with activation date in the past and expire date in the futur.
    $data = $this->getTestVideoData($id);
    $data->itemData->publishingdata->validFromDesktop = $pastDate;
    $data->itemData->publishingdata->validUntilDesktop = $futureDate;

    $videoData = $this->postVideoData($data);
    $videoEntity = $this->loadVideoEntity($videoData->value);

    // Make sure this is active.
    $this->assertEquals(1, $videoEntity->isPublished(), "Video
    $id should be created with status=1.");

    // Set expire date in the past and run cron.
    $videoEntity->get($videoFieldName)->first()->set('validto_ssc', $pastDate);
    $videoEntity->save();

    $this->cron->run();
    $videoEntity = $this->loadVideoEntity($videoData->value);
    $this->assertEquals(0, $videoEntity->isPublished(), "Video
    $id should be set to status=0 after cron run with expire date in the past.");

    // Set expire date to the future, the activation date in the past
    // and run cron.
    $videoEntity->get($videoFieldName)->first()->set('validto_ssc', $futureDate);
    $videoEntity->get($videoFieldName)->first()->set('validfrom_ssc', $pastDate);
    $videoEntity->save();

    $this->cron->run();
    /** @var \Drupal\media\MediaInterface $videoEntity */
    $videoEntity = $this->loadVideoEntity($videoData->value);
    $this->assertEquals(1, $videoEntity->isPublished(), "Video
    $id should be set to status=1 after cron run with activation date in the past.");
  }

  /**
   * Test the video entity 9: test deleted trigger.
   */
  public function testDeletedVideoTrigger() {
    $id = 10;

    // Send delete=0 now.
    $data = $this->getTestVideoData($id);
    $videoData = $this->postVideoData($data);

    $videoEntity = $this->loadVideoEntity($videoData->value);
    $this->assertEquals($videoEntity->get("status")->getString(), 1, "Video id
    $id should be status=1 before deletion.");
    $count = $this->countVideos();

    $data = $this->getTestVideoDeleteData($id);
    $videoData = $this->postVideoData($data);

    $videoEntity = $this->loadVideoEntity($videoData->value);
    $this->assertNull($videoEntity, "Video id $id should be deleted.");
    $this->assertEquals($count - 1, $this->countVideos(), "Counting all videos 
    after deletion. Video id $id should be deleted.");
  }

  /**
   * Test existing preview image not re-created on update.
   */
  public function testPreviewImageOnUpdate() {
    $id = 11;

    $data = $this->getTestVideoData($id);
    $videoData = $this->postVideoData($data);

    $videoEntity = $this->loadVideoEntity($videoData->value);
    $preview_image_id = $videoEntity->get('field_preview_image')->target_id;

    // Posting the same request again should not change the preview image.
    $videoData = $this->postVideoData($data);
    $this->assertEquals($preview_image_id, $this->loadVideoEntity($videoData->value)->get('field_preview_image')->target_id, 'Preview image stay the same after resubmitting request.');
  }

  /**
   * Test the created video entity.
   */
  public function testMappedFields() {
    /* Disabled for now */
    return;
    /*
    $data = $this->getTestVideoData();
    $videoData = $this->postVideoData($data);

    $videoEntity = $this->loadVideoEntity($videoData->value);

    // $videoField = $videoEntity->get('field_video');
     */
  }

  /**
   * Attach fields to media bundle using the FieldUiTestTrait.
   */
  protected function attachFields() {
    /* Disabled for now */
    return;
    /*
    $this->drupalLogin($this->adminUser);
    $bundle_path = 'admin/structure/media/manage/nexx_video';

    $this->submitFieldUi($bundle_path, 'test_description', NULL, 'text_long');

    foreach ($this->vocabularies as $vocabulary) {
    $storage_edit = [];
    $field_edit = [];
    $storage_edit['settings[target_type]'] = 'taxonomy_term';
    $storage_edit['cardinality_number'] = '2';
    $field_edit['settings[handler_settings][target_bundles][' .
    $vocabulary->id() . ']'] = TRUE;
    $this->submitFieldUi($bundle_path, 'test_' . $vocabulary->label(), NULL,
    'field_ui:entity_reference:taxonomy_term');
    }
     */
  }

  /**
   * Creates a new field through the Field UI.
   *
   * @param string $bundle_path
   *   Admin path of the bundle that the new field is to be attached to.
   * @param string $field_name
   *   The field name of the new field storage.
   * @param string $label
   *   (optional) The label of the new field. Defaults to a random string.
   * @param string $field_type
   *   (optional) The field type of the new field storage. Defaults to
   *   'test_field'.
   * @param array $storage_edit
   *   (optional) $edit parameter for drupalPostForm() on the second step
   *   ('Storage settings' form).
   * @param array $field_edit
   *   (optional) $edit parameter for drupalPostForm() on the third step ('Field
   *   settings' form).
   */
  protected function submitFieldUi($bundle_path, $field_name, $label = NULL, $field_type = 'test_field', array $storage_edit = [], array $field_edit = []) {
    $label = $label ?: $this->randomString();
    $initial_edit = [
      'new_storage_type' => $field_type,
      'label' => $label,
      'field_name' => $field_name,
    ];

    // Allow the caller to set a NULL path in case they navigated to the right
    // page before calling this method.
    if ($bundle_path !== NULL) {
      $bundle_path = "$bundle_path/fields/add-field";
    }

    // First step: 'Add field' page.
    $this->drupalPostForm($bundle_path, $initial_edit, t('Save and continue'));

    // Second step: 'Storage settings' form.
    $this->drupalPostForm(NULL, $storage_edit, t('Save field settings'));

    // Third step: 'Field settings' form.
    $this->drupalPostForm(NULL, $field_edit, t('Save settings'));
  }

}

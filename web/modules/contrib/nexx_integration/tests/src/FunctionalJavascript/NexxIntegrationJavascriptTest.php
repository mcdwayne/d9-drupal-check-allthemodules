<?php

namespace Drupal\Tests\nexx_integration\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the JavaScript functionality of the block add filter.
 *
 * @group nexx_integration
 */
class NexxIntegrationJavascriptTest extends WebDriverTestBase {

  use NexxTestTrait;

  public static $modules = [
    'taxonomy',
    'nexx_integration',
    'nexx_integration_test',
    'field_ui',
    'field',
  ];

  /**
   * Test that editing a video does not delete data.
   *
   * @see https://www.drupal.org/node/2927183
   */
  public function testVideoEditing() {
    $id = 10;
    $data = $this->getTestVideoData($id);
    $videoData = $this->postVideoData($data);

    $videoEditPage = 'media/' . $videoData->value . '/edit';
    $this->drupalGet($videoEditPage);
    $this->getSession()->getPage()->pressButton('Save');

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
    $this->assertEquals(
      $data->itemData->publishingdata->validFromDesktop,
      $videoField->validfrom_ssc
    );
    $this->assertEquals(
      $data->itemData->publishingdata->validUntilDesktop,
      $videoField->validto_ssc
    );
    $this->assertEquals($data->itemData->publishingdata->allowedOnMobile, $videoField->isMOBILE);
    $this->assertEquals(
      $data->itemData->publishingdata->validFromMobile,
      $videoField->validfrom_mobile
    );
    $this->assertEquals(
      $data->itemData->publishingdata->validUntilMobile,
      $videoField->validto_mobile
    );
    $this->assertEquals($data->itemData->publishingdata->isPublished, $videoField->active);
    $this->assertEquals($data->itemData->publishingdata->isDeleted, $videoField->isDeleted);
    $this->assertEquals($data->itemData->publishingdata->isBlocked, $videoField->isBlocked);
  }

}

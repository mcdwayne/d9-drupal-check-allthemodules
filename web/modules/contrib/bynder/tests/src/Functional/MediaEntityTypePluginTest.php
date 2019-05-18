<?php

namespace Drupal\Tests\bynder\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the Media entity type plugin.
 *
 * @group bynder
 */
class MediaEntityTypePluginTest extends BrowserTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'bynder',
    'media',
    'bynder_test_module',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([
      'administer media types',
      'create media',
      'view media',
    ]));
  }

  /**
   * Tests media entity type plugin.
   */
  public function testMediaTypePlugin() {
    // Create test image.
    $image = current($this->getTestFiles('image'));
    // Assert extra field on form display.
    $this->drupalGet('admin/structure/media/manage/media_type/form-display');
    $this->getSession()->getPage()->hasSelect('edit-fields-edit-on-bynder-type');
    $this->drupalGet('admin/structure/media/manage/media_type');
    // Check that the type provider is set to bynder.
    $this->assertSession()->fieldValueEquals('source', 'bynder');
    // Check field mapping.
    $this->assertSession()->fieldValueEquals('field_map[description]', 'field_description');
    $this->assertSession()->fieldValueEquals('field_map[name]', 'name');
    $this->assertSession()->fieldValueEquals('field_map[type]', 'field_type');
    $this->assertSession()->fieldValueEquals('field_map[video_preview_urls]', 'field_video_preview_urls');
    $this->assertSession()->fieldValueEquals('field_map[thumbnail_urls]', 'field_thumbnail_urls');
    $this->assertSession()->fieldValueEquals('field_map[width]', 'field_width');
    $this->assertSession()->fieldValueEquals('field_map[height]', 'field_height');
    $this->assertSession()->fieldValueEquals('field_map[created]', 'field_date_created');
    $this->assertSession()->fieldValueEquals('field_map[modified]', 'field_date_modified');

    $bynder_data = [
      'dateModified' => '2016-12-13T21:10:55Z',
      'type' => 'image',
      'brandId' => '9C9D9172-1234-1234-91689AFFC4E661B4',
      'fileSize' => '5176',
      'id' => '4DFD39C5-1234-1234-8714AFEE1A617618',
      'height' => '194',
      'description' => 'Some description',
      'idHash' => '11121c3560d2d01f',
      'name' => 'images',
      'tags' => [
        0 => 'startups',
        1 => 'london',
      ],
      'orientation' => 'landscape',
      'width' => '259',
      'datePublished' => '2016-12-09T14:17:48Z',
      'copyright' => '',
      'extension' => [
        0 => 'jpeg',
      ],
      'userCreated' => 'Jon Doe',
      'dateCreated' => '2016-12-09T14:18:24Z',
      'archive' => 0,
      'watermarked' => 0,
      'limited' => 0,
      'thumbnails' => [
        'mini' => 'https://d2csxpduxe849s.cloudfront.net/media/2AF9718D.jpg',
        'webimage' => file_create_url($image->uri),
        'thul' => 'https://d2csxpduxe849s.cloudfront.net/media/9F22A5BA1D47.jpg',
      ],
      'views' => 6,
      'downloads' => 0,
      'activeOriginalFocusPoint' => [
        'y' => 97,
        'x' => 129.5,
      ],
    ];

    \Drupal::state()->set('bynder.bynder_test_media_info', $bynder_data);
    $this->drupalGet('media/add/media_type');
    $this->getSession()->getPage()->fillField('name[0][value]', 'Media name test');
    $this->getSession()->getPage()->fillField('field_media_uuid[0][value]', '4DFD39C5-1234-1234-8714AFEE1A617618');
    $this->getSession()->getPage()->pressButton('Save');

    $this->assertSession()->elementTextContains('css', '.field--name-field-media-uuid', '4DFD39C5-1234-1234-8714AFEE1A617618');
    $this->assertSession()->responseContains('/files/styles/thumbnail/public/' . $image->name);
    $this->assertSession()->responseNotContains('/files/styles/thumbnail/public/media-icons/generic/bynder_no_image.png');
    $this->assertSession()->elementTextContains('css', '.field--name-field-description', 'Some description');
    $this->assertSession()->elementTextContains('css', '.field--name-field-type', 'image');
    $this->assertSession()->elementTextContains('css', '.field--name-field-width', '259');
    $this->assertSession()->elementTextContains('css', '.field--name-field-height', '194');
    $this->assertSession()->elementTextContains('css', '.field--name-field-date-created', '2016-12-09T14:18:24Z');
    $this->assertSession()->elementTextContains('css', '.field--name-field-date-modified', '2016-12-13T21:10:55Z');

    unset($bynder_data['thumbnails']['webimage']);
    \Drupal::state()->set('bynder.bynder_test_media_info', $bynder_data);
    $this->drupalGet('media/add/media_type');
    $this->getSession()->getPage()->fillField('name[0][value]', 'Media name test');
    $this->getSession()->getPage()->fillField('field_media_uuid[0][value]', '4DFD39C5-1234-1234-8714AFEE1A617618');
    $this->getSession()->getPage()->pressButton('Save');

    // Check default thumbnail.
    $this->assertSession()->responseContains('/files/styles/thumbnail/public/media-icons/generic/bynder_no_image.png');
    $this->assertSession()->responseNotContains('/files/styles/thumbnail/public/' . $image->name);
    // Check is link to asset exists on edit page.
    $this->drupalGet('media/1/edit');
    $this->getSession()->getPage()->hasLink("edit asset's metadata on Bynder.");
  }

}

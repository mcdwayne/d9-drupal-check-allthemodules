<?php

namespace Drupal\Tests\media_entity_soundcloud\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Functional\MediaFunctionalTestCreateMediaTypeTrait;

/**
 * Tests for Soundcloud embed formatter.
 *
 * @group media_entity_soundcloud
 */
class SoundcloudEmbedFormatterTest extends BrowserTestBase {

  use MediaFunctionalTestCreateMediaTypeTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'media_entity_soundcloud',
    'media',
    'link',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an admin user with permissions to administer and create media.
    $account = $this->drupalCreateUser([
      // Media entity permissions.
      'view media',
      'create media',
      'update media',
      'update any media',
      'delete media',
      'delete any media',
    ]);

    // Login the user.
    $this->drupalLogin($account);
  }

  /**
   * Tests adding and editing a soundcloud embed formatter.
   */
  public function testSoundcloudEmbedFormatter() {
    $assert = $this->assertSession();

    $media_type = $this->createMediaType(['bundle' => 'soundcloud'], 'soundcloud');

    $source_field = $media_type->getSource()->getSourceFieldDefinition($media_type);
    $this->assertSame('field_media_soundcloud', $source_field->getName());
    $this->assertSame('string', $source_field->getType());

    entity_get_form_display('media', $media_type->id(), 'default')
      ->setComponent('field_media_soundcloud', [
        'type' => 'string_textfield',
      ])
      ->save();

    entity_get_display('media', $media_type->id(), 'full')
      ->setComponent('field_media_soundcloud', [
        'type' => 'soundcloud_embed',
      ])
      ->save();

    // Create a soundcloud media entity.
    $this->drupalGet('media/add/' . $media_type->id());

    $page = $this->getSession()->getPage();
    $page->fillField('name[0][value]', 'Soundcloud');
    $page->fillField('field_media_soundcloud[0][value]', 'https://soundcloud.com/winguy/billie-jean-remix-ft');
    $page->pressButton('Save');

    // Assert that we're looking at a media item.
    $assert->addressMatches('/^\/media\/[0-9]+$/');
    // Assert that the formatter exists on this page.
    $assert->elementExists('css', 'iframe');
  }

}

<?php

namespace Drupal\Tests\media_entity_dreambroker\Functional;

use Drupal\Tests\media\Functional\MediaFunctionalTestBase;

/**
 * Tests for Dreambroker embed formatter.
 *
 * @group media_entity_dreambroker
 */
class DreambrokerEmbedFormatterTest extends MediaFunctionalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'media_entity_dreambroker',
    'link',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests adding and editing a dreambroker embed formatter.
   */
  public function testManageEmbedFormatter() {
    // Test and create one media type.
    $bundle = $this->createMediaType('dreambroker', ['id' => 'dreambroker']);

    // We need to fix widget and formatter config for the default field.
    $source = $bundle->getSource();
    $source_field = $source->getSourceFieldDefinition($bundle);
    // Use the default widget and settings.
    $component = \Drupal::service('plugin.manager.field.widget')
      ->prepareConfiguration('string', []);

    // @todo Replace entity_get_form_display() when #2367933 is done.
    // https://www.drupal.org/node/2872159.
    entity_get_form_display('media', $bundle->id(), 'default')
      ->setComponent($source_field->getName(), $component)
      ->save();

    // Assert that the media type has the expected values before proceeding.
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id());
    $this->assertFieldByName('label', $bundle->label());
    $this->assertFieldByName('source', 'dreambroker');

    // Assert that the new field types configurations have been successfully
    // saved.
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id() . '/fields');
    $xpath = $this->xpath('//*[@id=:id]/td', [':id' => 'field-media-dreambroker']);
    $this->assertEquals('Dream Broker Url', (string) $xpath[0]->getText());
    $this->assertEquals('field_media_dreambroker', (string) $xpath[1]->getText());
    $this->assertEquals('Text (plain)', (string) $xpath[2]->find('css', 'a')->getText());

    // Display settings.
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id() . '/display');

    // Set and save the settings of the new field types.
    $edit = [
      'fields[field_media_dreambroker][parent]' => 'content',
      'fields[field_media_dreambroker][region]' => 'content',
      'fields[field_media_dreambroker][label]' => 'above',
      'fields[field_media_dreambroker][type]' => 'dreambroker_embed',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->responseContains('Your settings have been saved.');

    // Assert that the link url formatter exists on this page.
    $this->assertSession()->responseContains('Dream Broker Url');
    $this->assertSession()->responseContains('Embedded Dream Broker (Responsive).');

    // Create and save the media with a Dream Broker media code.
    $this->drupalGet('media/add/' . $bundle->id());

    // Random image url from Dream Broker.
    $dreambroker_url = 'https://www.dreambroker.com/channel/1zcdkjfg/h8q6cakv';

    $edit = [
      'name[0][value]' => 'Title',
      'field_media_dreambroker[0][value]' => $dreambroker_url,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Assert that the media has been successfully saved.
    $this->assertSession()->responseContains('Title');
    $this->assertSession()->responseContains('dreambroker-thumbnails/h8q6cakv.png');
  }

}

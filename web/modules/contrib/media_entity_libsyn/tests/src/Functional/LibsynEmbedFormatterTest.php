<?php

namespace Drupal\Tests\media_entity_libsyn\Functional;

use Drupal\Tests\media\Functional\MediaFunctionalTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests for Libsyn embed formatter.
 *
 * @group media_entity_libsyn
 */
class LibsynEmbedFormatterTest extends MediaFunctionalTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'media_entity_libsyn',
    'media',
    'node',
    'field_ui',
    'views_ui',
    'block',
    'link',
    'system',
  ];

  /**
   * The test user.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $adminUser;

  /**
   * The test media bundle.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $bundle;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a test libsyn media bundle.
    $this->bundle = $this->createMediaType('libsyn');

    // Add breadcrumb block so that FieldUiTestTrait can use it.
    $this->placeBlock('system_breadcrumb_block');
  }

  /**
   * Tests adding and editing a libsyn embed formatter.
   */
  public function testLibsynEmbedFormatter() {
    /* @var $entity_type_manager \Drupal\Core\Entity\EntityTypeManagerInterface */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $assert = $this->assertSession();

    // Assert that the media bundle has the expected values before proceeding.
    $this->drupalGet('admin/structure/media/manage/' . $this->bundle->id());
    $this->assertFieldByName('label', $this->bundle->label());
    $this->assertFieldByName('source', 'libsyn');

    // Media added a URL source field for us, so let's find it.
    $source_field = $this->bundle
      ->getSource()
      ->getSourceFieldDefinition($this->bundle);

    // Change the storage settings: Allow only external links, disable allow
    // link text.
    $edit = [
      'settings[link_type]' => 16,
      'settings[title]' => 0,
    ];
    $this->drupalPostForm('admin/structure/media/manage/' . $this->bundle->id() . '/fields/' . $source_field->getConfig($this->bundle->id())->id(), $edit, t('Save settings'));
    // Check if the field has been saved successfully.
    $assert->pageTextMatches('/Saved .+ configuration/');
    $assert->pageTextContains($source_field->getName());

    // Set the source url display to libsym_embed.
    $edit = [
      'fields[' . $source_field->getName() . '][label]' => 'above',
      'fields[' . $source_field->getName() . '][type]' => 'libsyn_embed',
      // Ensure the field is not hidden from view.
      'fields[' . $source_field->getName() . '][region]' => 'content',
    ];
    $this->drupalPostForm('admin/structure/media/manage/' . $this->bundle->id() . '/display', $edit, t('Save'));
    $this->assertText('Your settings have been saved.');

    // Add a media entity.
    $findable_name = 'podcast_entity_' . $this->randomMachineName();
    $podcast_url = 'http://behindtheblue.libsyn.com/may-5-2017-dr-christian-lattermann-dr-carl-mattacola-equine-rider-injury-research';
    $edit = [
      'name[0][value]' => $findable_name,
      $source_field->getName() . '[0][uri]' => $podcast_url,
    ];
    $this->drupalPostForm('media/add/' . $this->bundle->id(), $edit, 'Save');
    // We should be redirected to the media listing page.
    $assert->pageTextContains($findable_name);

    // Load the entity we just added.
    $media_entity = $entity_type_manager
      ->getStorage('media')
      ->loadByProperties(['name' => $findable_name]);
    $media_entity = reset($media_entity);
    $this->assertEquals($podcast_url, $media_entity->{$source_field->getName()}->uri);

    // Add a node content type.
    $node_type = $this->createContentType();

    // Add an entity reference field.
    $field_name = mb_strtolower('e_r_' . $this->randomMachineName());
    $storage_edit = [
      'settings[target_type]' => 'media',
    ];
    $field_edit = [
      'settings[handler_settings][target_bundles][' . $this->bundle->id() . ']' => TRUE,
    ];
    $this->fieldUIAddNewField('admin/structure/types/manage/' . $node_type->id(), $field_name, $field_name, 'entity_reference', $storage_edit, $field_edit);

    // Make sure the entity reference display renders the entity rather than
    // linking to it.
    $edit = [
      'fields[field_' . $field_name . '][type]' => 'entity_reference_entity_view',
    ];
    $this->drupalPostForm('admin/structure/types/manage/' . $node_type->id() . '/display', $edit, 'Save');

    // Add a node that references our media entity.
    $edit = [
      'title[0][value]' => 'Test Node',
      'field_' . $field_name . '[0][target_id]' => $media_entity->label() . ' (' . $media_entity->id() . ')',
    ];
    $this->drupalPostForm('node/add/' . $node_type->id(), $edit, 'Save');

    // Assert that the formatter exists on this page.
    $this->assertFieldByXPath('//iframe');
  }

}

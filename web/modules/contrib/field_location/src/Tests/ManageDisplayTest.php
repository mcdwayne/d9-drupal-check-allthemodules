<?php

namespace Drupal\field_location\Tests;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\simpletest\WebTestBase;
use Drupal\googlemaps\Entity\GoogleMap;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\node\Entity\NodeType;

/**
 * @group field_location
 */
class ManageDisplayTest extends WebTestBase {

  use FieldUiTestTrait;

  const FIELD_TYPE = 'location';
  const FIELD_NAME = 'field_location_test';

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    // Fields will be managed using dummy content type of "node" entity.
    'node',
    // Needed to display block with breadcrumbs.
    'block',
    // Required by "FieldUiTestTrait".
    'field_ui',
    // Contains schemes definitions for the "location" field type.
    'field_location_test',
  ];
  /**
   * Dummy content type.
   *
   * @var NodeType
   */
  protected $contentType;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('system_breadcrumb_block');

    $this->drupalLogin($this->drupalCreateUser([
      'access content',
      'bypass node access',
      'administer node fields',
      'administer node display',
      'administer node form display',
      'administer content types',
    ]));

    $this->contentType = $this->drupalCreateContentType([
      'type' => $this->randomMachineName(),
    ]);
  }

  /**
   * Tests field creation.
   */
  public function testCreateField() {
    $this->fieldUIAddNewField('admin/structure/types/manage/' . $this->contentType->id(), static::FIELD_NAME, NULL, static::FIELD_TYPE, [
      'settings[googlemaps_library]' => EntityAutocomplete::getEntityLabels(
        $this->container->get('entity_type.manager')->getStorage(GoogleMap::ENTITY_TYPE)->loadMultiple(['field_location'])
      ),
    ]);
    // @todo Continue here.
  }

}

<?php

namespace Drupal\entity_gallery\Tests\Views;

use Drupal\entity_gallery\EntityGalleryCreationTrait;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the integration of entity_gallery_revision table of entity gallery module.
 *
 * @group entity_gallery
 */
class RevisionRelationshipsTest extends ViewTestBase {

  use EntityGalleryCreationTrait {
    getEntityGalleryByTitle as drupalGetEntityGalleryByTitle;
    createEntityGallery as drupalCreateEntityGallery;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_gallery' , 'entity_gallery_test_views', 'node');

  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), array('entity_gallery_test_views'));
  }

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_gallery_revision_egid', 'test_entity_gallery_revision_vid');

  /**
   * Create an entity gallery with revision and rest result count for both views.
   */
  public function testEntityGalleryRevisionRelationship() {
    $entity_gallery = $this->drupalCreateEntityGallery();
    // Create revision of the entity gallery.
    $entity_gallery_revision = clone $entity_gallery;
    $entity_gallery_revision->setNewRevision();
    $entity_gallery_revision->save();
    $column_map = array(
      'vid' => 'vid',
      'entity_gallery_field_data_entity_gallery_field_revision_egid' => 'entity_gallery_entity_gallery_revision_egid',
      'egid_1' => 'egid_1',
    );

    // Here should be two rows.
    $view_egid = Views::getView('test_entity_gallery_revision_egid');
    $this->executeView($view_egid, array($entity_gallery->id()));
    $resultset_egid = array(
      array(
        'vid' => '1',
        'entity_gallery_entity_gallery_revision_egid' => '1',
        'egid_1' => '1',
      ),
      array(
        'vid' => '2',
        'entity_gallery_revision_egid' => '1',
        'entity_gallery_entity_gallery_revision_egid' => '1',
        'egid_1' => '1',
      ),
    );
    $this->assertIdenticalResultset($view_egid, $resultset_egid, $column_map);

    // There should be only one row with active revision 2.
    $view_vid = Views::getView('test_entity_gallery_revision_vid');
    $this->executeView($view_vid, array($entity_gallery->id()));
    $resultset_vid = array(
      array(
        'vid' => '2',
        'entity_gallery_entity_gallery_revision_egid' => '1',
        'egid_1' => '1',
      ),
    );
    $this->assertIdenticalResultset($view_vid, $resultset_vid, $column_map);
  }

}

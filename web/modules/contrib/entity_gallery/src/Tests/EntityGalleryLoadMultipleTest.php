<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\entity_gallery\Entity\EntityGallery;

/**
 * Tests the loading of multiple entity galleries.
 *
 * @group entity_gallery
 */
class EntityGalleryLoadMultipleTest extends EntityGalleryTestBase {

  /**
   * Enable Views to test the frontpage against EntityGallery::loadMultiple()
   * results.
   *
   * @var array
   */
  public static $modules = array('views');

  protected function setUp() {
    parent::setUp();
    $web_user = $this->drupalCreateUser(array('create article entity galleries', 'create page entity galleries'));
    $this->drupalLogin($web_user);
  }

  /**
   * Creates four entity galleries and ensures that they are loaded correctly.
   */
  function testEntityGalleryMultipleLoad() {
    $entity_gallery1 = $this->drupalCreateEntityGallery(array('type' => 'article', 'status' => 1));
    $entity_gallery2 = $this->drupalCreateEntityGallery(array('type' => 'article', 'status' => 1));
    $entity_gallery3 = $this->drupalCreateEntityGallery(array('type' => 'article', 'status' => 0));
    $entity_gallery4 = $this->drupalCreateEntityGallery(array('type' => 'page', 'status' => 0));

    // Load entity galleries with only a condition. Entity galleries 3 and 4
    // will be loaded.
    $entity_galleries = entity_load_multiple_by_properties('entity_gallery', array('status' => 0));
    $this->assertEqual($entity_gallery3->label(), $entity_galleries[$entity_gallery3->id()]->label(), 'Entity gallery was loaded.');
    $this->assertEqual($entity_gallery4->label(), $entity_galleries[$entity_gallery4->id()]->label(), 'Entity gallery was loaded.');
    $count = count($entity_galleries);
    $this->assertTrue($count == 2, format_string('@count entity galleries loaded.', array('@count' => $count)));

    // Load entity galleries by egid. Entity galleries 1, 2 and 4 will be
    // loaded.
    $entity_galleries = EntityGallery::loadMultiple(array(1, 2, 4));
    $count = count($entity_galleries);
    $this->assertTrue(count($entity_galleries) == 3, format_string('@count entity galleries loaded', array('@count' => $count)));
    $this->assertTrue(isset($entity_galleries[$entity_gallery1->id()]), 'Entity gallery is correctly keyed in the array');
    $this->assertTrue(isset($entity_galleries[$entity_gallery2->id()]), 'Entity gallery is correctly keyed in the array');
    $this->assertTrue(isset($entity_galleries[$entity_gallery4->id()]), 'Entity gallery is correctly keyed in the array');
    foreach ($entity_galleries as $entity_gallery) {
      $this->assertTrue(is_object($entity_gallery), 'Entity gallery is an object');
    }
  }
}

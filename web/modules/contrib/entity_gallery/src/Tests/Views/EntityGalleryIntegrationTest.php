<?php

namespace Drupal\entity_gallery\Tests\Views;

/**
 * Tests the entity gallery integration into views.
 *
 * @group entity_gallery
 */
class EntityGalleryIntegrationTest extends EntityGalleryTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_gallery_view');

  /**
   * Tests basic entity gallery view with a entity gallery type argument.
   */
  public function testEntityGalleryViewTypeArgument() {
    // Create two entity gallery types with three entity galleries each.
    $types = array();
    $all_egids = array();
    for ($i = 0; $i < 2; $i++) {
      $type = $this->drupalCreateGalleryType(['name' => '<em>' . $this->randomMachineName() . '</em>']);
      $types[] = $type;

      for ($j = 0; $j < 5; $j++) {
        // Ensure the right order of the entity galleries.
        $entity_gallery = $this->drupalCreateEntityGallery(array('type' => $type->id(), 'created' => REQUEST_TIME - ($i * 5 + $j)));
        $entity_galleries[$type->id()][$entity_gallery->id()] = $entity_gallery;
        $all_egids[] = $entity_gallery->id();
      }
    }

    $this->drupalGet('test-entity-gallery-view');
    $this->assertResponse(404);

    $this->drupalGet('test-entity-gallery-view/all');
    $this->assertResponse(200);
    $this->assertEgids($all_egids);

    foreach ($types as $type) {
      $this->drupalGet("test-entity-gallery-view/{$type->id()}");
      $this->assertEscaped($type->label());
      $this->assertEgids(array_keys($entity_galleries[$type->id()]));
    }
  }

  /**
   * Ensures that a list of entity galleries appear on the page.
   *
   * @param array $expected_egids
   *   An array of entity gallery IDs.
   */
  protected function assertEgids(array $expected_egids = array()) {
    $result = $this->xpath('//span[@class="field-content"]');
    $egids = array();
    foreach ($result as $element) {
      $egids[] = (int) $element;
    }
    $this->assertEqual($egids, $expected_egids);
  }

}

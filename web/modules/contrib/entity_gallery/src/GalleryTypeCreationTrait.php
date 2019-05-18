<?php

namespace Drupal\entity_gallery;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\entity_gallery\Entity\EntityGalleryType;

/**
 * Provides methods to create gallery type from given values.
 *
 * This trait is meant to be used only by test classes.
 */
trait GalleryTypeCreationTrait {

  /**
   * Creates a custom gallery type based on default settings.
   *
   * @param array $values
   *   An array of settings to change from the defaults.
   *   Example: 'type' => 'foo'.
   *
   * @return \Drupal\entity_gallery\Entity\EntityGalleryType
   *   Created gallery type.
   */
  protected function createGalleryType(array $values = array()) {
    // Find a non-existent random type name.
    if (!isset($values['type'])) {
      do {
        $id = strtolower($this->randomMachineName(8));
      } while (EntityGalleryType::load($id));
    }
    else {
      $id = $values['type'];
    }
    $values += array(
      'type' => $id,
      'name' => $id,
      'gallery_type' => 'node',
      'gallery_type_bundles' => [],
    );
    $type = EntityGalleryType::create($values);
    $status = $type->save();
    entity_gallery_create_entity_reference_field($type, $type->getGalleryType(), $type->getGalleryTypeBundles());

    if ($this instanceof \PHPUnit_Framework_TestCase) {
      $this->assertSame($status, SAVED_NEW, (new FormattableMarkup('Created gallery type %type.', array('%type' => $type->id())))->__toString());
    }
    else {
      $this->assertEqual($status, SAVED_NEW, (new FormattableMarkup('Created gallery type %type.', array('%type' => $type->id())))->__toString());
    }

    return $type;
  }

}

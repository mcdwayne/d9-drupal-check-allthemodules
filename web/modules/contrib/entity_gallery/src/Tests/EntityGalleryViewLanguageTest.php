<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the entity gallery language extra field display.
 *
 * @group entity_gallery
 */
class EntityGalleryViewLanguageTest extends EntityGalleryTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_gallery', 'datetime', 'language');

  /**
   * Tests the language extra field display.
   */
  public function testViewLanguage() {
    // Add Spanish language.
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Set language field visible.
    entity_get_display('entity_gallery', 'page', 'full')
      ->setComponent('langcode')
      ->save();

    // Create an entity gallery in Spanish.
    $entity_gallery = $this->drupalCreateEntityGallery(array('langcode' => 'es'));

    $this->drupalGet($entity_gallery->urlInfo());
    $this->assertText('Spanish', 'The language field is displayed properly.');
  }

}

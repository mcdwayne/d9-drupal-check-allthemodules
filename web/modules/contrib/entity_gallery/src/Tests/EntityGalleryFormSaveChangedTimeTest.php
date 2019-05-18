<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\entity_gallery\GalleryTypeCreationTrait;
use Drupal\entity_gallery\EntityGalleryCreationTrait;

/**
 * Tests updating the changed time after API and FORM entity save.
 *
 * @group entity_gallery
 */
class EntityGalleryFormSaveChangedTimeTest extends WebTestBase {

  use GalleryTypeCreationTrait {
    createGalleryType as drupalCreateGalleryType;
  }
  use EntityGalleryCreationTrait {
    createEntityGallery as drupalCreateEntityGallery;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_gallery', 'node');

  /**
   * An user with permissions to create and edit articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authorUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an entity gallery type.
    $this->drupalCreateContentType(array(
      'type' => 'article',
      'name' => 'Article',
    ));

    // Create an entity gallery type.
    $this->drupalCreateGalleryType(array(
      'type' => 'article',
      'name' => 'Article',
    ));

    $this->authorUser = $this->drupalCreateUser(['access entity galleries', 'create article entity galleries', 'edit any article entity galleries'], 'author');
    $this->drupalLogin($this->authorUser);

    // Create one entity gallery of the above entity gallery type .
    $this->drupalCreateEntityGallery(array(
      'type' => 'article',
    ));
  }

  /**
   * Test the changed time after API and FORM save without changes.
   */
  public function testChangedTimeAfterSaveWithoutChanges() {
    $entity_gallery = entity_load('entity_gallery', 1);
    $changed_timestamp = $entity_gallery->getChangedTime();

    $entity_gallery->save();
    $entity_gallery = entity_load('entity_gallery', 1, TRUE);
    $this->assertEqual($changed_timestamp, $entity_gallery->getChangedTime(), "The entity's changed time wasn't updated after API save without changes.");

    // Ensure different save timestamps.
    sleep(1);

    // Save the entity gallery on the regular entity gallery edit form.
    $this->drupalPostForm('gallery/1/edit', array(), t('Save'));

    $entity_gallery = entity_load('entity_gallery', 1, TRUE);
    $this->assertNotEqual($changed_timestamp, $entity_gallery->getChangedTime(), "The entity's changed time was updated after form save without changes.");
  }
}

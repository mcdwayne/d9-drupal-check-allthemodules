<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\entity_gallery\GalleryTypeCreationTrait;
use Drupal\entity_gallery\EntityGalleryCreationTrait;

/**
 * Tests help functionality for entity galleries.
 *
 * @group entity_gallery
 */
class EntityGalleryHelpTest extends WebTestBase {

  use GalleryTypeCreationTrait {
    createGalleryType as drupalCreateGalleryType;
  }
  use EntityGalleryCreationTrait {
    createEntityGallery as drupalCreateEntityGallery;
  }

  /**
   * Modules to enable.
   *
   * @var array.
   */
  public static $modules = array('block', 'entity_gallery', 'node', 'help');

  /**
   * The name of the test entity gallery type to create.
   *
   * @var string
   */
  protected $testType;

  /**
   * The test 'entity gallery help' text to be checked.
   *
   * @var string
   */
  protected $testText;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user.
    $admin_user = $this->drupalCreateUser(array(
      'administer entity gallery types',
      'administer entity galleries',
      'bypass entity gallery access',
    ));

    $this->drupalLogin($admin_user);
    $this->drupalPlaceBlock('help_block');

    $this->testType = 'type';
    $this->testText = t('Help text to find on entity gallery forms.');

    // Create entity gallery type.
    $this->drupalCreateGalleryType(array(
      'type' => $this->testType,
      'help' => $this->testText,
    ));
  }

  /**
   * Verifies that help text appears on entity gallery add/edit forms.
   */
  public function testEntityGalleryShowHelpText() {
    // Check the entity gallery add form.
    $this->drupalGet('gallery/add/' . $this->testType);
    $this->assertResponse(200);
    $this->assertText($this->testText);

    // Create entity gallery and check the entity gallery edit form.
    $entity_gallery = $this->drupalCreateEntityGallery(array('type' => $this->testType));
    $this->drupalGet('gallery/' . $entity_gallery->id() . '/edit');
    $this->assertResponse(200);
    $this->assertText($this->testText);
  }
}

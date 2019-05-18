<?php

namespace Drupal\entity_gallery\Tests\Views;

use Drupal\entity_gallery\GalleryTypeCreationTrait;
use Drupal\entity_gallery\EntityGalleryCreationTrait;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Base class for all entity gallery tests.
 */
abstract class EntityGalleryTestBase extends ViewTestBase {

  use GalleryTypeCreationTrait {
    createGalleryType as drupalCreateGalleryType;
  }
  use EntityGalleryCreationTrait {
    getEntityGalleryByTitle as drupalGetEntityGalleryByTitle;
    createEntityGallery as drupalCreateEntityGallery;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_gallery_test_views', 'node');

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType([
        'type' => 'page',
        'name' => 'Basic page',
        'display_submitted' => FALSE,
      ]);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }
    $this->accessHandler = \Drupal::entityManager()->getAccessControlHandler('node');

    if ($import_test_views) {
      ViewTestData::createTestViews(get_class($this), array('entity_gallery_test_views'));
    }
  }

}

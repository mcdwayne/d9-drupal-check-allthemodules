<?php

namespace Drupal\entity_gallery\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the entity_gallery row plugin.
 *
 * @group entity_gallery
 * @see \Drupal\entity_gallery\Plugin\views\row\EntityGalleryRow
 */
class RowPluginTest extends EntityGalleryTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_gallery');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_gallery_row_plugin');

  /**
   * Contains all entity galleries used by this test.
   *
   * @var array
   */
  protected $entity_galleries;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateGalleryType(array('type' => 'article'));

    // Create two entity galleries.
    for ($i = 0; $i < 2; $i++) {
      $this->entity_galleries[] = $this->drupalCreateEntityGallery(
        array(
          'type' => 'article',
        )
      );
    }
  }

  /**
   * Tests the entity gallery row plugin.
   */
  public function testRowPlugin() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_entity_gallery_row_plugin');
    $view->initDisplay();
    $view->setDisplay('page_1');
    $view->initStyle();
    $view->rowPlugin->options['view_mode'] = 'full';

    // Test with view_mode full.
    $output = $view->preview();
    $output = $renderer->renderRoot($output);
    foreach ($this->entity_galleries as $entity_gallery) {
      $this->assertTrue(strpos($output, $entity_gallery->label()) !== FALSE, 'Make sure the entity gallery appears in the output of the view.');
    }

    // Test with teasers.
    $view->rowPlugin->options['view_mode'] = 'teaser';
    $output = $view->preview();
    $output = $renderer->renderRoot($output);
    foreach ($this->entity_galleries as $entity_gallery) {
      $this->assertTrue(strpos($output, $entity_gallery->label()) !== FALSE, 'Make sure the entity gallery appears in the output of the view.');
    }
  }

}

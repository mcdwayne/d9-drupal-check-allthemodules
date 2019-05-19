<?php
/**
 * @file
 * Contains \Drupal\widget_block\WidgetBlockBuilder.
 */

namespace Drupal\widget_block;

use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Lazy builder for rendering a widget block.
 */
class WidgetBlockLazyBuilder {

  /**
   * The Widget Block configuration storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $widgetBlockConfigStorage;

  /**
   * Create a WidgetBlockLazyBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    // Setup object members.
    $this->widgetBlockConfigStorage = $entity_manager->getStorage('widget_block_config');
  }

  /**
   * Get the widget block configuration storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   An instance of EntityStorageInterface.
   */
  protected function getWidgetBlockConfigStorage() {
    return $this->widgetBlockConfigStorage;
  }

  /**
   * Lazy builder callback for building the widget markup.
   *
   * @param string $widget_block_config_id
   *   A unique widget block configuration identifier.
   *
   * @return array
   *   A renderable array which contains the widget markup.
   */
  public function build($widget_block_config_id) {
    // Initialize $markup variable to NULL as default behavior.
    $markup = NULL;

    /** @var \Drupal\widget_block\Entity\WidgetBlockConfigInterface $widget_block_config */
    $widget_block_config = $this->getWidgetBlockConfigStorage()->load($widget_block_config_id);

    // Check whether the widget block configuration was resolved.
    if ($widget_block_config) {
      // Get the widget block markup.
      $markup = $widget_block_config->getMarkup();
    }

    return $markup ? $markup->toRenderable() : [];
  }

}

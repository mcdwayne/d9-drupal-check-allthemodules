<?php

namespace Drupal\vefl;

use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Helper class that holds all the main Display Suite helper functions.
 */
class Vefl {

  use StringTranslationTrait;

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutManager;

  /**
   * Constructs a new class object.
   *
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_manager
   *   The layout plugin manager.
   */
  public function __construct(LayoutPluginManagerInterface $layout_manager) {
    $this->layoutManager = $layout_manager;
  }

  /**
   * Gets Display Suite layouts.
   */
  public function getLayouts() {
    static $layouts = FALSE;

    if (!$layouts) {
      $layouts = $this->layoutManager->getDefinitions();
    }

    return $layouts;
  }

  /**
   * Gets Display Suite layouts.
   */
  public function getLayoutOptions($layouts = []) {
    if (empty($layouts)) {
      $layouts = $this->getLayouts();
    }

    // Converts layouts array to options.
    $layout_options = [];
    foreach ($layouts as $key => $layout_definition) {
      $optgroup = $this->t('Other');

      // Create new layout option group.
      if (!empty($layout_definition->getCategory())) {
        $optgroup = (string) $layout_definition->getCategory();
      }

      if (!isset($layout_options[$optgroup])) {
        $layout_options[$optgroup] = [];
      }

      // Stack the layout.
      $layout_options[$optgroup][$key] = $layout_definition->getLabel();
    }

    // If there is only one $optgroup, move it to the root.
    if (count($layout_options) < 2) {
      $layout_options = reset($layout_options);
    }

    return $layout_options;
  }

  /**
   * Returns action fields for views exposed form.
   */
  public static function getFormActions() {
    $actions = [
      'sort_by' => t('Sort by'),
      'sort_order' => t('Sort order'),
      'items_per_page' => t('Items per page'),
      'offset' => t('Offset'),
      'submit' => t('Submit button'),
      'reset' => t('Reset button'),
    ];

    return $actions;
  }

}

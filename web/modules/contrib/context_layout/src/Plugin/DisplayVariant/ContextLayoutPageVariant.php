<?php

namespace Drupal\context_layout\Plugin\DisplayVariant;

use Drupal\context\ContextManager;
use Drupal\context\Plugin\DisplayVariant\ContextBlockPageVariant;
use Drupal\context_layout\Plugin\ContextLayout\ContextLayoutManager;
use Drupal\Core\Theme\ThemeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a page display variant that decorates the main content with blocks.
 *
 * @see \Drupal\Core\Block\MainContentBlockPluginInterface
 * @see \Drupal\Core\Block\MessagesBlockPluginInterface
 *
 * @PageDisplayVariant(
 *   id = "context_layout_page",
 *   admin_label = @Translation("Page with blocks and layout")
 * )
 */
class ContextLayoutPageVariant extends ContextBlockPageVariant {

  /**
   * The theme manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManager
   */
  protected $themeManager;

  /**
   * The context layout manager service.
   *
   * @var \Drupal\context_layout\Plugin\ContextLayout\ContextLayoutManager
   */
  protected $contextLayoutManager;

  /**
   * Constructs a new ContextBlockPageVariant.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\context\ContextManager $contextManager
   *   The context module manager.
   * @param \Drupal\Core\Theme\ThemeManager $themeManager
   *   The theme manager.
   * @param Drupal\context_layout\Plugin\ContextLayout\ContextLayoutManager $contextLayoutManager
   *   The context layout manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextManager $contextManager, ThemeManager $themeManager, ContextLayoutManager $contextLayoutManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $contextManager);
    $this->themeManager = $themeManager;
    $this->contextLayoutManager = $contextLayoutManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('context.manager'),
      $container->get('theme.manager'),
      $container->get('plugin.manager.context_layout')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();

    $build['#cache']['tags'] = ['context_layout_page', $this->getPluginId()];

    // Load regions from current theme.
    $theme = $this->themeManager->getActiveTheme()->getName();
    $theme_regions = array_keys(system_region_list($theme));

    // Load layout from active contexts.
    // @todo How should we handle multiple contexts with a layout?
    $layout = NULL;
    foreach ($this->contextManager->getActiveContexts() as $context) {
      $third_party_settings = $context->getThirdPartySettings('context_layout');
      if (!empty($third_party_settings['layout'][$theme])) {
        $layout = $third_party_settings['layout'][$theme];
      }
    }

    // Exit early if we don't have a layout.
    if (!$layout) {
      return $build;
    }

    /** @var \Drupal\Core\Layout\LayoutDefault $layout */
    $layout = $this->contextLayoutManager->loadLayout($layout);
    $layout_regions = $layout->getPluginDefinition()->getRegionNames();

    // Remove blocks that are assigned to unavailable layout regions (derived
    // from theme).
    foreach ($build as $key => $value) {
      $in_theme = in_array($key, $theme_regions);
      $in_layout = in_array($key, $layout_regions);
      if ($in_theme && !$in_layout) {
        unset($build[$key]);
      }
    }

    return $layout->build($build);
  }

}

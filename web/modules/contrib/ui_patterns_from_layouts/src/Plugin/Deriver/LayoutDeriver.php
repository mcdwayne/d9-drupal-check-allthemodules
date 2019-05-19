<?php

namespace Drupal\ui_patterns_from_layouts\Plugin\Deriver;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\ui_patterns\Plugin\Deriver\AbstractPatternsDeriver;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LayoutDeriver.
 */
class LayoutDeriver extends AbstractPatternsDeriver {

  /**
   * Layout plugin manager service.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * LayoutDeriver constructor.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\TypedData\TypedDataManager $typed_data_manager
   *   Typed data manager service.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   *   Layout plugin manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   */
  public function __construct(
    $base_plugin_id,
    TypedDataManager $typed_data_manager,
    LayoutPluginManagerInterface $layout_plugin_manager,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($base_plugin_id, $typed_data_manager);
    $this->layoutPluginManager = $layout_plugin_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('typed_data_manager'),
      $container->get('plugin.manager.core.layout'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPatterns() {
    $patterns = [];
    if (!$this->moduleHandler->moduleExists('ui_patterns_layouts')) {
      $layout_definitions = $this->layoutPluginManager->getDefinitions();
      /** @var \Drupal\Core\Layout\LayoutDefinition $layout_definition */
      foreach ($layout_definitions as $layout_definition) {
        $patterns[] = $this->getPatternDefinition($this->getPatternDefinitionArray($layout_definition));
      }
    }
    return $patterns;
  }

  /**
   * Returns a pattern definition array based on the given LayoutDefinition.
   *
   * @param \Drupal\Core\Layout\LayoutDefinition $layout_definition
   *   LayoutDefinition.
   *
   * @return array
   *   Pattern definition array.
   */
  public function getPatternDefinitionArray(LayoutDefinition $layout_definition) {
    $pattern_definition = [];

    $pattern_definition['id'] = $layout_definition->id();
    $pattern_definition['base path'] = $layout_definition->getPath();
    $pattern_definition['file name'] =
      $layout_definition->getProvider() . '.layouts.yml';
    $pattern_definition['provider'] = $layout_definition->getProvider();

    $pattern_definition['label'] = $layout_definition->getLabel();
    $pattern_definition['description'] = $layout_definition->getDescription();
    $pattern_definition['fields'] = $layout_definition->getRegions();
    $example_values = $layout_definition->get('example_values');
    if (is_array($example_values) && isset($example_values['base']) && isset($example_values['base']['data']) && is_array($example_values['base']['data'])) {
      foreach ($example_values['base']['data'] as $field => $preview) {
        $pattern_definition['fields'][$field]['preview'] = $preview;
      }
    }
    else {
      foreach ($pattern_definition['fields'] as &$field) {
        $field['preview'] = $field['label'];
      }
    }

    $pattern_definition['use'] =
      $layout_definition->getTemplatePath() . '/' . $layout_definition->getTemplate() . '.html.twig';

    return $pattern_definition;
  }

}

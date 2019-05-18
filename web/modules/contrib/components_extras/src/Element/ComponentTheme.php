<?php

namespace Drupal\components_extras\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Class ComponentTheme.
 *
 * @package Drupal\components_extras\Element
 * @RenderElement("component")
 */
class ComponentTheme extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'components_extras',
      '#component' => NULL,
      '#pre_render' => [
        [get_class($this), 'preRenderComponent'],
      ],
    ];
  }

  /**
   * Pre-render callback: builds a renderable array for a component.
   *
   * @param array $element
   *   A renderable array containing a #component property, which is a valid
   *   component (provided by the components module).
   *
   * @return array
   *   A renderable array representing a component.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function preRenderComponent(array $element) {
    /** @var \Drupal\components_extras\ComponentThemeManagerInterface $component_theme */
    $component_theme = \Drupal::service('plugin.manager.component_theme');
    $plugin = $component_theme->getDefinition($element['#component']);
    $element['#component_definition'] = $plugin;
    $element['#component_variables'] = [];
    if (!empty($plugin['variables'])) {
      foreach ($plugin['variables'] as $variable) {
        if (!empty($element["#$variable"])) {
          $element['#component_variables'][$variable] = $element["#$variable"];
        }
      }
    }
    return $element;
  }

}

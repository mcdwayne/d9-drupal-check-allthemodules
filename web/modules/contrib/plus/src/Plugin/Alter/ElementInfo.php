<?php

namespace Drupal\plus\Plugin\Alter;

use Drupal\plus\Plugin\Theme\Template\PrerenderInterface;
use Drupal\plus\Plugin\Theme\Template\ProcessInterface;
use Drupal\plus\Plugin\ThemePluginBase;
use Drupal\plus\Plus;

/**
 * Implements hook_element_info_alter().
 *
 * @ingroup plugins_alter
 *
 * @Alter("element_info", {
 *   weight: -1000
 * })
 */
class ElementInfo extends ThemePluginBase implements AlterInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(&$types, &$context1 = NULL, &$context2 = NULL) {
    // Sort the types for easier debugging.
    ksort($types, SORT_NATURAL);

    $template_manager = $this->theme->getTemplateManager();

    foreach (array_keys($types) as $type) {
      $element = &$types[$type];

      // Add extra variables as defaults to all elements.
      foreach ($this->theme->defaultVariables() as $key => $value) {
        if (!isset($element["#$key"])) {
          $element["#$key"] = $value;
        }
      }

      // Only continue if the type isn't "form" (as it messes up AJAX).
      if ($type !== 'form') {
        $regex = "/^$type/";

        // Add necessary #process callbacks.
        if ($this->theme instanceof ProcessInterface) {
          $element['#process'][] = [get_class($this->theme), 'process'];
        }

        $definitions = $this->theme->getProcessManager()->getDefinitionsLike($regex);
        foreach ($definitions as $plugin_id => $definition) {
          $instance = $this->theme->getProcessManager()->createInstance($plugin_id, ['theme' => $this->theme]);
          if ($instance instanceof ProcessInterface) {
            Plus::addCallback($element['#process'], [$definition['class'], 'process'], $definition['replace'], $definition['action']);
          }
        }

        // Add necessary #pre_render callbacks.
        if ($this->theme instanceof PrerenderInterface) {
          $element['#pre_render'][] = [get_class($this->theme), 'preRender'];
        }

        foreach ($this->theme->getPrerenderManager()->getDefinitionsLike($regex) as $plugin_id => $definition) {
          $instance = $this->theme->getProcessManager()->createInstance($plugin_id, ['theme' => $this->theme]);
          if ($instance instanceof PrerenderInterface) {
            Plus::addCallback($element['#pre_render'], [$definition['class'], 'preRender'], $definition['replace'], $definition['action']);
          }
        }
      }
    }
  }

}

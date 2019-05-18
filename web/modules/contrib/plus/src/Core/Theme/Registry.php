<?php

namespace Drupal\plus\Core\Theme;

use Drupal\plus\Plugin\Theme\Template\PreprocessInterface;
use Drupal\plus\Plus;
use Drupal\Core\Theme\Registry as CoreRegistry;

/**
 * Modifies core's "theme.registry" service.
 */
class Registry extends CoreRegistry {

  /**
   * The Plus service.
   *
   * @var \Drupal\plus\Plus
   */
  protected $plus;

  /**
   * {@inheritdoc}
   */
  protected function build() {
    // @todo Refactor to modify original methods instead of double processing.
    parent::build();

    $registry = $this->registry[$this->theme->getName()];

    // Sort the registry alphabetically (for easier debugging).
    ksort($registry);

    $active_theme = Plus::getActiveTheme();

    // Add additional default variables to all theme hooks.
    foreach (array_keys($registry) as $hook) {
      // Skip theme hooks that don't set variables.
      if (!isset($registry[$hook]['variables'])) {
        continue;
      }
      $registry[$hook]['variables'] += $active_theme->defaultVariables();
    }

    // Ensure paths to templates are set properly. This allows templates to
    // be moved around in a theme without having to constantly ensuring that
    // the theme's hook_theme() definitions have the correct static "path" set.
    foreach ($active_theme->getAncestry() as $ancestor) {
      $current_theme = $ancestor->getName() === $active_theme->getName();
      $theme_path = $ancestor->getPath();
      foreach ($ancestor->fileScan('/\.html\.twig$/', 'templates') as $file) {
        $hook = str_replace('-', '_', str_replace('.html.twig', '', $file->filename));
        $path = dirname($file->uri);
        $incomplete = !isset($registry[$hook]) || strrpos($hook, '__');

        // Create a new theme hook. This primarily happens when theme hook
        // suggestion templates are created. To prevent the new hook from
        // inheriting parent hook's "template", it must be manually set here.
        // @see https://www.drupal.org/node/2871551
        if (!isset($registry[$hook])) {
          $registry[$hook] = [
            'template' => str_replace('.html.twig', '', $file->filename),
          ];
        }

        // Always ensure that "path", "type" and "theme path" are properly set.
        $registry[$hook]['path'] = $path;
        $registry[$hook]['type'] = $current_theme ? 'theme' : 'base_theme';
        $registry[$hook]['theme path'] = $theme_path;

        // Flag incomplete.
        if ($incomplete) {
          $registry[$hook]['incomplete preprocess functions'] = TRUE;
        }
      }
    }

    // Discover all the theme's preprocess plugins.
    $plugins = $active_theme->getTemplateManager()->getDefinitions();
    ksort($plugins, SORT_NATURAL);

    // Iterate over the preprocess plugins.
    foreach ($plugins as $plugin_id => $definition) {
      /** @var \Drupal\plus\PluginBase $plugin */
      $plugin = $active_theme->getTemplateManager()->createInstance($plugin_id);

      // Ignore templates that don't preprocess.
      if (!($plugin instanceof PreprocessInterface)) {
        continue;
      }

      $incomplete = !isset($registry[$plugin_id]) || strrpos($plugin_id, '__');
      if (!isset($registry[$plugin_id])) {
        $registry[$plugin_id] = [];
      }

      array_walk($registry, function (&$info, $hook) use ($plugin, $plugin_id, $definition) {
        if ($hook === $plugin_id || strpos($hook, $plugin_id . '__') === 0) {
          if (!isset($info['preprocess functions'])) {
            $info['preprocess functions'] = [];
          }
          Plus::addCallback($info['preprocess functions'], [$definition['class'], 'preprocess'], $definition['replace'], $definition['action']);
          $info['preprocess functions'] = array_unique($info['preprocess functions']);
        }
      });

      if ($incomplete) {
        $registry[$plugin_id]['incomplete preprocess functions'] = TRUE;
      }
    }

    // Allow core to post process.
    $this->postProcessExtension($registry, $this->theme);

    return $registry;
  }

}

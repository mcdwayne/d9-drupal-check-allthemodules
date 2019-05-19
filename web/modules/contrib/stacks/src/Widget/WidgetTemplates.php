<?php

namespace Drupal\stacks\Widget;

use Drupal;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Class WidgetTemplates.
 */
class WidgetTemplates {

  /**
   * Returns the correct path to the template directory in the active theme
   *
   * @return string The path of the frontend theme.
   */
  static public function templateDir() {
    // Get path to current active theme. Need to grab from config.
    $config = Drupal::config('system.theme');
    $default_active_theme = $config->get('default');

    if (empty($default_active_theme)) {
      // No active theme set.
      return FALSE;
    }

    // Look for the /stacks directory in the active theme.
    $directory_path = drupal_get_path('theme', $default_active_theme) . '/stacks';

    if (is_dir($directory_path)) {
      return $directory_path;
    }

    drupal_set_message(t("There isn't a stacks directory in the theme. Copy the 'stacks' directory in the module into your frontend theme and clear the drupal cache."), 'error');
    return '';
  }

  /**
   * Returns a list of templates based on file names.
   */
  static public function getAllTemplates($widget_bundle_name = '') {
    $base_dir = WidgetTemplates::templateDir();

    if (!is_dir($base_dir)) {
      $templates = [];
      $templates[] = [
        'name' => 'default',
      ];

      return $templates;
    }

    $templates = [];
    $di = new \RecursiveDirectoryIterator($base_dir);
    foreach (new \RecursiveIteratorIterator($di) as $path_to_file => $file) {

      if ($file->getExtension() == 'twig') {

        $filename = $file->getFilename();
        $path_to_dir = $file->getPath();

        // Only process those directories ending in "templates" (skip ajax ones)
        if(substr($path_to_dir, -9) !== 'templates') {
          continue;
        }

        // Extract the template name from the file name.
        $variation_name = $file->getBasename('.html.twig');
        $twig_filename = $file->getBasename('.html.twig');

        // Go up two levels to get the bundle name from directory name
        $bundle = $file->getPathInfo()->getPathInfo()->getBasename();

        if (empty($widget_bundle_name) || $bundle == $widget_bundle_name) {

          $bundle_machine_name = str_replace('-', '_', $bundle);
          $variation_machine_name = str_replace(['-', $bundle_machine_name . '__'], ['_', ''], $variation_name);
          $variation_machine_name_friendly = ucwords(str_replace('_', ' ', $variation_machine_name));

          // We need to know if this is a content feed or content list.
          $grid_type = false;
          if (substr($bundle, 0, 11) === 'contentfeed') {
            $grid_type = 'contentfeed';
            // In content feed cases, we need to add the template for the ajax
            $ajax_path = $file->getPathInfo()->getPath() . '/ajax';
            $templates[] = [
              'path_to_dir' => $ajax_path,
              'bundle' => $bundle_machine_name,
              'bundle_theme' => $bundle,
              'twig_filename' => 'ajax_' . $twig_filename,
              'variation_machine_name' => $variation_machine_name,
              'variation_machine_name_friendly' => $variation_machine_name_friendly,
              'grid_type' => $grid_type,
            ];
          }
          else if (substr($bundle, 0, 11) === 'contentlist') {
            $grid_type = 'contentlist';
          }

          $templates[] = [
            'path_to_dir' => $path_to_dir,
            'bundle' => $bundle_machine_name,
            'bundle_theme' => $bundle,
            'twig_filename' => $twig_filename,
            'variation_machine_name' => $variation_machine_name,
            'variation_machine_name_friendly' => $variation_machine_name_friendly,
            'grid_type' => $grid_type,
          ];
        }
      }
    }

    return $templates;
  }

  /**
   * Returns an array of available options, to use in the template select options list.
   */
  static public function getTemplatesSelect() {
    $templates = WidgetTemplates::getAllTemplates();

    if (count($templates) < 1) {
      return ['default' => t('Default')];
    }

    $options = [];
    foreach ($templates as $template) {
      if(substr($template['path_to_dir'], -5) === '/ajax') {
        continue;
      }
      $key = $template['variation_machine_name'];
      $bundle = $template['bundle'];
      $options[$bundle][$key] = $template['variation_machine_name_friendly'];
    }

    return $options;
  }

  /**
   * Returns an array of available options, to use in the template select options list.
   */
  static public function getThemeSelect() {
    $config = \Drupal::service('config.factory')
      ->getEditable('stacks.settings');
    $template_config = $config->get("template_themes_config");
    return $template_config;
  }

}

<?php

namespace Drupal\drush_commands\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;

class Commands extends ControllerBase {

  public function list() {

    // Get a list of installed modules.
    $modules = \Drupal::moduleHandler()->getModuleList();

    // Recursive scan of the entire doc root to find .drush.inc files.
    $directory = new \RecursiveDirectoryIterator(DRUPAL_ROOT, \FilesystemIterator::FOLLOW_SYMLINKS);
    $iterator  = new \RecursiveIteratorIterator($directory);
    $regex     = new \RegexIterator($iterator, '/\.drush\.inc$/i');

    // Get all commands for installed modules.
    $commands = [];
    foreach ($regex as $info) {

      // Get the module name from the file name.
      $drush_file  = basename($info->getPathname());
      $module_name = str_ireplace('.drush.inc', '', $drush_file);

      // Only load the inc file if the module is installed.
      if (isset($modules[$module_name])) {
        include_once($info->getPathname());
        $function = $module_name . '_drush_command';
        if (function_exists($function)) {
          $module_commands = call_user_func($function);
          foreach ($module_commands as &$module_command) {
            $module_command['module'] = $module_name;
          }
          $commands += $module_commands;
        }
      }
    }

    // Build table rows.
    $rows = [];
    foreach ($commands as $command => $info) {

      // Build the aliases.
      $aliases = [];
      if (isset($info['aliases'])) {
        foreach ($info['aliases'] as $alias) {
          $aliases[] = 'drush ' . $alias;
        }
      }

      // Build the row.
      $rows[] = [
        $info['module'],
        'drush ' . $command,
        Markup::create(implode('<br>', $aliases)),
        (isset($info['description']) ? $info['description'] : ''),
      ];
    }

    return [
      '#theme'  => 'table',
      '#header' => ['Module', 'Command', 'Aliases', 'Description'],
      '#rows'   => $rows,
      '#empty'  => t('There are no detectable drush commands.'),
    ];
  }
}

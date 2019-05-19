<?php
/**
 * @file
 * Unused Modules Drush support.
 */

/**
 * Implements hook_drush_command().
 */
function unused_modules_drush_command() {
  $items = [];
  $items['unused-modules'] = [
    'description' => 'Show unused modules or projects.',
    'aliases' => ['um', 'unused_modules'],
    'arguments' => [
      'type' => 'Options "projects" and "modules". Show modules or projects (defaults to projects).',
      'show' => 'Options "all" and "disabled". Show only disabled modules (defaults to disabled).',
    ],
    'examples' => [
      'Show projects that are unused.' => 'drush unused-modules projects disabled',
      'As above, shorthand.' => 'drush um',
      'As above, include projects with enabled modules.' => 'drush unused-modules projects disabled',
      'Show modules that are unused.' => 'drush unused-modules modules disabled',
      'As above, include enabled modules.' => 'drush unused-modules modules disabled',
    ],
  ];
  return $items;
}

/**
 * Implements hook_drush_help().
 */
function unused_modules_drush_help($section) {
  switch ($section) {
    case 'drush:unused-modules':
      return dt("Show modules and projects that are unused.");
  }
}

/**
 * Drush command callback.
 */
function drush_unused_modules($type = "projects", $show = "disabled") {

  // Print projects.
  if ($type == 'projects') {
    if ($show == 'all') {
      drush_unused_modules_show_projects('all');
    }
    elseif ($show == 'disabled') {
      drush_unused_modules_show_projects('disabled');
    }
    else {
      drush_set_error("unknown 'show' argument " . $show . ". See drush unused-modules --help");
    }
  }
  // Print modules.
  elseif ($type == 'modules') {
    if ($show == 'all') {
      drush_unused_modules_show_modules('all');
    }
    elseif ($show == 'disabled') {
      drush_unused_modules_show_modules('disabled');
    }
    else {
      drush_set_error("unknown 'show' argument " . $show . ". See drush unused-modules --help");
    }
  }
  else {
    drush_set_error("unknown 'type' argument " . $type . ". See drush unused-modules --help");
  }
}

/**
 * Drush callback.
 *
 * Prints a table with orphaned projects.
 *
 * @param string $op
 *   Either 'all' or 'disabled'.
 *
 * @return string
 *   themed table.
 */
function drush_unused_modules_show_projects($op = 'all') {
  /** @var \Drupal\unused_modules\UnusedModulesHelperService $helper */
  $helper = \Drupal::service('unused_modules.helper');
  $modules = $helper->getModulesByProject();

  $header = [
    'Project',
    'Project has Enabled Modules',
    'Project Path',
  ];

  // With Drush, the first row is the header.
  $rows = [];
  $rows[] = $header;

  foreach ($modules as $module) {
    if ($op == 'all') {
      $rows[$module->projectName] = [
        $module->projectName,
        $module->projectHasEnabledModules ? t("Yes") : t("No"),
        $module->projectPath,
      ];
    }
    elseif ($op == 'disabled') {
      if (!$module->projectHasEnabledModules) {
        $rows[$module->projectName] = [
          $module->projectName,
          $module->projectHasEnabledModules ? t("Yes") : t("No"),
          $module->projectPath,
        ];
      }
    }
  }

  // Note: header is always there.
  if (count($rows) === 1) {
    drush_print("Hurray, no orphaned projects!");
  }
  else {
    drush_print_table($rows, TRUE);
  }
}

/**
 * Drush callback.
 *
 * Prints a table with orphaned modules.
 *
 * @param string $op
 *   Either 'all' or 'disabled'.
 *
 * @return string
 *   themed table.
 */
function drush_unused_modules_show_modules($op = 'all') {
  /** @var \Drupal\unused_modules\UnusedModulesHelperService $helper */
  $helper = \Drupal::service('unused_modules.helper');
  $modules = $helper->getModulesByProject();

  $header = [
    'Project',
    'Module',
    'Module enabled',
    'Project has Enabled Modules',
    'Project Path',
  ];

  // With Drush, the first row is the header.
  $rows = [];
  $rows[] = $header;

  foreach ($modules as $module) {
    if ($op == 'all') {
      $rows[$module->getName()] = [
        $module->projectName,
        $module->getName(),
        $module->moduleIsEnabled ? t("Yes") : t("No"),
        $module->projectHasEnabledModules ? t("Yes") : t("No"),
        $module->projectPath,
      ];
    }
    elseif ($op == 'disabled') {
      if (!$module->projectHasEnabledModules) {
        $rows[$module->getName()] = [
          $module->projectName,
          $module->getName(),
          $module->moduleIsEnabled ? t("Yes") : t("No"),
          $module->projectHasEnabledModules ? t("Yes") : t("No"),
          $module->projectPath,
        ];
      }
    }
  }

  // Note: header is always there.
  if (count($rows) === 1) {
    drush_print("Hurray, no orphaned modules!");
  }
  else {
    drush_print_table($rows, TRUE);
  }
}

/**
 * Implements hook_drush_command_alter().
 */
function unused_modules_drush_command_alter(&$command) {
  if ($command['command'] == 'audit_extensions') {
    $command['checks'][] = [
      'name' => 'Unused',
      'location' => __DIR__ . '/unused_modules.site_audit.inc',
    ];
  }
}

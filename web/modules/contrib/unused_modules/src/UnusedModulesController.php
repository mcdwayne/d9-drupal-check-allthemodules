<?php

namespace Drupal\unused_modules;

use Drupal\Core\Controller\ControllerBase;

/**
 * Page callbacks.
 */
class UnusedModulesController extends ControllerBase {

  /**
   * Returns a table with orphaned projects.
   *
   * @param string $filter
   *   Either 'all' or 'disabled'.
   *
   * @return array
   *   table render array.
   */
  public function renderProjectsTable($filter) {
    /** @var \Drupal\unused_modules\UnusedModulesHelperService $helper */
    $helper = \Drupal::service('unused_modules.helper');
    $modules = $helper->getModulesByProject();

    $header = [
      'Project',
      'Project has Enabled Modules',
      'Project Path',
    ];

    $rows = [];
    foreach ($modules as $module) {
      if ($filter === 'all') {
        $rows[$module->projectName] = [
          $module->projectName,
          $module->projectHasEnabledModules ? t("Yes") : t("No"),
          $module->projectPath,
        ];
      }
      elseif ($filter === 'disabled') {
        if (!$module->projectHasEnabledModules) {
          $rows[$module->projectName] = [
            $module->projectName,
            $module->projectHasEnabledModules ? t("Yes") : t("No"),
            $module->projectPath,
          ];
        }
      }
    }

    if (!$rows) {
      return [
        '#type' => 'markup',
        '#markup' => t("Hurray, no orphaned projects!"),
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

  /**
   * Returns a table with orphaned modules.
   *
   * @param string $filter
   *   Either 'all' or 'disabled'.
   *
   * @return array
   *   Table render array.
   */
  public function renderModulesTable($filter) {
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

    $rows = [];
    foreach ($modules as $module) {
      if ($filter === 'all') {
        $rows[$module->getName()] = [
          $module->projectName,
          $module->getName(),
          $module->moduleIsEnabled ? t("Yes") : t("No"),
          $module->projectHasEnabledModules ? t("Yes") : t("No"),
          $module->projectPath,
        ];
      }
      elseif ($filter === 'disabled') {
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

    if (!$rows) {
      return [
        '#type' => 'markup',
        '#markup' => t("Hurray, no orphaned modules!"),
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

}

<?php

namespace Drupal\dashboard_connector\Checker;

use Drupal\update\UpdateFetcherInterface;
use Drupal\update\UpdateManagerInterface;

/**
 * Provides a module status checker.
 */
class ModuleStatusChecker extends CheckerBase {

  /**
   * {@inheritdoc}
   */
  public function getChecks() {
    $checks = [];
    if ($available = update_get_available(TRUE)) {
      module_load_include('inc', 'update', 'update.compare');

      $modules = update_calculate_project_data($available);
      $checks = [];
      foreach ($modules as $module) {
        $check = $this->buildCheck('module', $module['name'], $this->getDescription($module), $this->getAlertLevel($module['status']));
        // Special case core updates.
        if ($module['name'] === 'drupal') {
          $check['type'] = 'core';
        }
        $checks[] = $check;
      }
    }
    return $checks;
  }

  /**
   * Determine the module status alert level.
   *
   * @param string $status
   *   The module status.
   *
   * @return string
   *   The alert level.
   */
  protected function getAlertLevel($status) {
    switch ($status) {
      case UpdateManagerInterface::NOT_CURRENT:
        $alert_level = 'warning';
        break;

      case UpdateManagerInterface::NOT_SECURE:
      case UpdateManagerInterface::NOT_SUPPORTED:
      case UpdateManagerInterface::REVOKED:
        $alert_level = 'error';
        break;

      default:
        $alert_level = 'notice';
        break;
    }
    return $alert_level;
  }

  /**
   * Provide a human readable description.
   *
   * @param array $module
   *   The module status information.
   *
   * @return string
   *   The check message.
   */
  protected function getDescription(array $module) {
    $status = $module['status'];

    switch ($status) {
      case UpdateManagerInterface::CURRENT:
        $message = $this->t('Up to date (@existing_version)', [
          '@existing_version' => $module['existing_version'],
        ]);
        break;

      case UpdateFetcherInterface::FETCH_PENDING:
        $message = $this->t('Fetch Pending');
        break;

      case UpdateFetcherInterface::NOT_FETCHED:
        $message = $this->t('Not fetched');
        break;

      case UpdateManagerInterface::NOT_SECURE:
        $message = $this->t('Not secure (@existing_version => @latest_version)', [
          '@existing_version' => $module['existing_version'],
          '@latest_version' => $module['latest_version'],
        ]);
        break;

      case UpdateManagerInterface::REVOKED:
      case UpdateManagerInterface::NOT_SUPPORTED:
        $message = $this->t('Unsupported (@existing_version)', ['@existing_version' => $module['existing_version']]);
        break;

      case UpdateFetcherInterface::NOT_CHECKED:
        $message = $this->t('Not checked');
        break;

      case UpdateManagerInterface::NOT_CURRENT:
        $message = $this->t('Not current (@existing_version => @latest_version)', [
          '@existing_version' => $module['existing_version'],
          '@latest_version' => $module['latest_version'],
        ]);
        break;

      default:
        $message = $this->t('Unknown');
        break;
    }
    return $message;
  }

}

<?php

namespace Drupal\autoslave\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

/**
 * Controller routines for autoslave routes.
 **/
class AutoslaveController extends ControllerBase {
  
  /**
   * Generates the settings page for AutoSlave.
   *
   * @return array
   *   The renderable settings dashboard.
   */
  public function autoslaveSettings() {
    if (!autoslave_is_driver_loaded()) {
      drupal_set_message(t('AutoSlave driver is not loaded'), 'warning');
    }
    return [
      '#markup' => $this->t('More help to come ...'),
    ];
  }
 
  /**
   * AutoSlave Status Page
   **/
  public function autoslaveStatus() {
  
    if (!autoslave_is_driver_loaded()) {
      drupal_set_message(t('AutoSlave driver is not loaded'), 'warning');
      return [
        '#markup' => '',
      ];
    }
  
    // Load .install files
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    $tasks = db_installer_object('autoslave');
  
    $databases = Database::getAllConnectionInfo();
    $output = '';
    foreach ($databases as $key => $targets) {
      foreach ($targets as $target => $conninfo) {
        $conninfos = empty($conninfo['driver']) ? $conninfo : [$conninfo];
        foreach ($conninfos as $conninfo) {
          if ($conninfo['driver'] != 'autoslave') {
            continue;
          }
          $output .= "<h2>[$key][$target]</h2>";
          $output .= $tasks->connectionStatusTable(Database::getConnection($target, $key));
          $output .= '<br>';
        }
      }
    }
  
    return [
      '#markup' => $output,
    ];
  }
  
  /**
   * AutoSlave Affected Tables Page
   **/
  function affectedTables() {
    if (!autoslave_is_driver_loaded()) {
      drupal_set_message(t('AutoSlave driver is not loaded'), 'warning');
    }
        
    $active = [];
    $inactive = [];
    $connection = Database::getConnection();
    $target = $connection->driver() === 'autoslave' ? $connection->determineSystemTarget() : NULL;
    $query = db_select('autoslave_affected_tables', 'a', ['target' => $target])
               ->fields('a', ['db_key', 'db_target', 'affected_table', 'expires'])
               ->orderBy('a.expires', 'DESC')
               ->execute()
               ->fetchAll(\PDO::FETCH_ASSOC);
  
  
    foreach ($query as $row) {
      if ($row['expires'] > $_SERVER['REQUEST_TIME']) {
        $row['expires'] = date('Y-m-d H:i:s', $row['expires']);
        $active[] = $row;
      }
      else {
        $row['expires'] = date('Y-m-d H:i:s', $row['expires']);
        $inactive[] = $row;
      }
    }
    
    $header = [t('Key'), t('Target'), t('Table'), t('Expires')];
    
    $build = [];
    $build['active-tables-title'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . t('Active!') . '</h2>',
    ];
    $build['active-tables'] = [
      '#type' => 'table',
      '#header' => array(t('Label'), t('Machine name'), t('Weight'), t('Operations')),
      '#rows' => $active,
    ];
    $build['inactive-tables-title'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . t('Inactive') . '</h2>',
    ];
    $build['inactive-tables'] = [
      '#type' => 'table',
      '#header' => array(t('Label'), t('Machine name'), t('Weight'), t('Operations')),
      '#rows' => $inactive,
    ];
  
    return $build;
  }
}
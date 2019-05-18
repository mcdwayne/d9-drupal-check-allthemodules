<?php

namespace Drupal\idcp_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;

class IdcpCoreController extends ControllerBase {

  public function installDemoData() {
    $demo_data_info = \Drupal::moduleHandler()->invokeAll('demo_data_info');

    $migration_plugin_manager = \Drupal::service('plugin.manager.migration');
    $message = new MigrateMessage();

    foreach ($demo_data_info as $info) {
      $migration = $migration_plugin_manager->createInstance($info['id'], $info);

      if ($migration) {
        $migration->getIdMap()->prepareUpdate();

        // $migration->setStatus(\Drupal\migrate\Plugin\MigrationInterface::STATUS_IDLE);
        // $executable = new MigrateBatchExecutable($migration, $message, $options);
        // $executable->batchImport();

        $executable = new MigrateExecutable($migration);
        $executable->import();
      }
    }

    return [
      '#markup' => t('Demo data has been installed.'),
    ];
  }
}
<?php

use Symfony\Component\Yaml\Yaml;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;

function migrate_drush_drush_command() {
  $items['migrate_drush_generate'] = [
    'description' => 'Generates a new migration skeleton for an entity type',
    'arguments' => [
      'entity_type_id' => 'Entity type.',
      'bundle' => 'bundle',
    ],
  ];
  $items['migrate_drush_run'] = [
    'description' => 'Runs migrations with a status reset',
    'arguments' => [
      'migrations' => 'Migrations separated by comma',
    ],
    'options' => [
      'keep_status' => 'Migrations separated by comma which will not have their status reset',
      'method' => 'Can be import or rollback.'
    ],
  ];
  return $items;
}

function drush_migrate_drush_generate($entity_type_id, $bundle = '') {
  foreach (\Drupal::service('entity_field.manager')->getFieldMap()[$entity_type_id] as $field_name => $data) {
    if (!$bundle || isset($data['bundles'][$bundle])) {
      $field_names[] = $field_name;
    }
  }
  $migration['id'] = $entity_type_id;
  $migration['source']['plugin'] = $entity_type_id;
  $migration['process'] = array_combine($field_names , $field_names);
  $migration['destination']['plugin'] = "entity:$entity_type_id";
  print Yaml::dump($migration, 2, 2);
}

function drush_migrate_drush_run($migrations) {
  \Drupal::keyValue('migrate_status')->deleteAll();
  $ids = explode(',', $migrations);
  $instances = \Drupal::service('plugin.manager.migration')->createInstances($ids);
  foreach (array_diff($ids, explode(',', drush_get_option('keep_status', ''))) as $migration_id) {
    $instances[$migration_id]->getIdMap()->prepareUpdate();
  }
  foreach ($instances as $id => $migration) {
    call_user_func([(new MigrateExecutable($migration, new MigrateMessage())), drush_get_option('method', 'import')]);
    $id_map = $migration->getIdMap();
    printf("%s processed %d STATUS_IMPORTED %d STATUS_NEEDS_UPDATE %d STATUS_FAILED %d\n",
      $id, $id_map->processedCount(), $id_map->importedCount() - $id_map->updateCount(), $id_map->updateCount(), $id_map->errorCount());
  }
}

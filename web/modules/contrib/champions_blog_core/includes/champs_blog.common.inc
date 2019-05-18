<?php


// Function to create simple URL's
function champs_blog_create_slug($string){
  $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
  $slug = strtolower($slug);
  return $slug;
}

// Cleanup templates.
// QUESTION:  does Drupal have a function of its own for this?
function champs_blog_dash_to_underscore($string) {
  $underString = strtr($string, '-', '_');
  return $underString;
}


/**
 * Take in the prefix of the file, the update ID and the module name as strings,
 * then install / update the configuration.
 */

function _update_or_install_config( String $prefix, String $update_id, String $module) {
  $updated = [];
  $created = [];

  /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
  $config_manager = \Drupal::service('config.manager');
  $files = glob(drupal_get_path('module', $module) . '/config/update_' . $update_id. '/' . $prefix . '*.yml') ;
  foreach ($files as $file) {
    $raw = file_get_contents($file);
    $value = \Drupal\Component\Serialization\Yaml::decode($raw);
    if(!is_array($value)) {
      throw new \RuntimeException(sprintf('Invalid YAML file %s'), $file);
    }

    $type = $config_manager->getEntityTypeIdByname(basename($file));
    $entity_manager = $config_manager->getEntityManager();
    $definition = $entity_manager->getDefinition($type);
    $id_key = $definition->getKey('id');
    $id = $value[$id_key];

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $entity_storage */
    $entity_storage = $entity_manager->getStorage($type);
    $entity = $entity_storage->load($id);
    if ($entity) {
      $entity = $entity_storage->updateFromStorageRecord($entity, $value);
      $entity->save();
      $updated[] = $id;
    }
    else {
      $entity = $entity_storage->createFromStorageRecord($value);
      $entity->save();
      $created[] = $id;
    }
  }

  return [
    'udpated' => $updated,
    'created' => $created,
  ];
}

/**
 * Take in the prefix of the file, the update ID and the module name as strings,
 * then install / update the configuration.
 */

function _reinstall_module_config(String $module) {
  $updated = [];
  $created = [];

  /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
  $config_manager = \Drupal::service('config.manager');
  $files = glob(drupal_get_path('module', $module) . '/config/install/*.yml') ;
  foreach ($files as $file) {
    $raw = file_get_contents($file);
    $value = \Drupal\Component\Serialization\Yaml::decode($raw);
    if(!is_array($value)) {
      throw new \RuntimeException(sprintf('Invalid YAML file %s'), $file);
    }

    $type = $config_manager->getEntityTypeIdByname(basename($file));
    $entity_manager = $config_manager->getEntityManager();
    $definition = $entity_manager->getDefinition($type);
    $id_key = $definition->getKey('id');
    $id = $value[$id_key];

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $entity_storage */
    $entity_storage = $entity_manager->getStorage($type);
    $entity = $entity_storage->load($id);
    if ($entity) {
      $entity = $entity_storage->updateFromStorageRecord($entity, $value);
      $entity->save();
      $updated[] = $id;
    }
    else {
      $entity = $entity_storage->createFromStorageRecord($value);
      $entity->save();
      $created[] = $id;
    }
  }

  return [
    'udpated' => $updated,
    'created' => $created,
  ];
}
<?php

namespace Drupal\advanced_update;

use Drupal\Console\Generator\Generator;
use Drupal\advanced_update\Entity\AdvancedUpdateEntity;

/**
 * Class UpdateGenerator.
 *
 * @package Drupal\advanced_update
 */
class UpdateGenerator extends Generator {

  /**
   * Generate a AdvancedUpdate class and an entity linked.
   *
   * @param string $module
   *    Module name to generate class.
   * @param string $description
   *    Short description of the functionality.
   *
   * @throws \Exception
   *    Throw an exception if an error occurred.
   */
  public function generate($module, $description) {
    $timestamp = time();
    $update_class = AdvancedUpdateEntity::generateClassName();

    // Creating the entity linked to the PHP class file.
    $entity = AdvancedUpdateEntity::create(array(
      'id' => strtolower($update_class),
      'label' => $description,
      'date' => $timestamp,
      'class_name' => $update_class,
      'module_name' => $module,
    ));
    $entity_saved = $entity->save();

    if (!$entity_saved) {
      $error = (string) \Drupal::translation()
        ->translate('Advanced Update not generated caused by a not saved entity');
      throw new \Exception($error);
    }

    // Clear all caches.
    drupal_flush_all_caches();
  }

}

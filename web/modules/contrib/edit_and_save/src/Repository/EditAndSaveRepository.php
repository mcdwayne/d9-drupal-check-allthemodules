<?php

namespace Drupal\edit_and_save\Repository;

/**
 * Class EditAndSaveRepository.
 *
 * @package Drupal\edit_and_save\Repository.
 */
class EditAndSaveRepository {

  /**
   * Helper function.
   *
   * Contains all form ids where the edit and save button is present. Gets all
   * the appropriate results from the global config.
   *
   * @return array
   *   An array of form id where The edit&save button should be shown.
   */
  public static function getAllowedForms() {
    $editAndSaveEntities = \Drupal::config('edit_and_save.settings')
      ->get('edit_and_save_entities');

    if (!$editAndSaveEntities) {
      return [];
    }
    $allowedValues = [];
    foreach ($editAndSaveEntities as $entityGlobalType => $entities) {
      foreach ($entities as $entity => $entityCategory) {
        foreach ($editAndSaveEntities[$entityGlobalType][$entity] as $bundleKey => $value) {
          if ($bundleKey === $value) {
            $allowedValues[] = $entity . '_' . $bundleKey . '_form';
            $allowedValues[] = $entity . '_' . $bundleKey . '_edit_form';
          }
        }
      }
    }

    return $allowedValues;
  }

  /**
   * Helper function that returns entities that are not supported.
   *
   * @return array
   *   An array of unsupported entity types.
   */
  public static function getUnsupportedEntityTypes() {
    $unsupportedEntityTypes = [
      'file',
      'contact_message',
    ];

    return $unsupportedEntityTypes;
  }

}

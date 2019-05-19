<?php

namespace Drupal\taxonomy_per_user;

use Drupal\Core\Access\AccessResult;

/**
 * Provides Access Check for taxonomy_per_user entity.
 *
 * @ingroup taxonomy_per_user
 */
abstract class TaxonomyPerUserAccessCheck {

  /**
   * {@inheritdoc}
   */
  public static function checkCreatorAccess($op = NULL, $vid = NULL) {

    // Admin: always.
    if (\Drupal::currentUser()->hasPermission('administer taxonomy')) {
      return AccessResult::allowed();
    }

    // If this user has permission to view vocabulary.
    $uid = \Drupal::currentUser()->id();
    $tpu = \Drupal::database()->select('taxonomy_per_user', 't')
      ->fields('t', ['user_id'])
      ->condition('target_id', $vid)
      ->condition('user_id,', $uid)
      ->execute()->fetchField();

    // If $tpu not empty is mean this user assigned permission form
    // taxonomy_per_user.
    // If the $vid is empty, covered taxonomy overview_form page.
    if (!empty($tpu) || empty($vid)) {
      return AccessResult::allowed();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public static function vocabularyTermCheckAccess($operation, $vid, $account) {
    switch ($operation) {
      case 'View':
        return AccessResult::allowedIfHasPermissions($account, ["view terms in $vid", 'administer taxonomy'], 'OR');

      case 'Create':
        return AccessResult::allowedIfHasPermissions($account, ["create terms in $vid", 'administer taxonomy'], 'OR');

      case 'Edit':
        return AccessResult::allowedIfHasPermissions($account, ["edit terms in $vid", 'administer taxonomy'], 'OR');

      case 'Delete':
        return AccessResult::allowedIfHasPermissions($account, ["delete terms in $vid", 'administer taxonomy'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

}

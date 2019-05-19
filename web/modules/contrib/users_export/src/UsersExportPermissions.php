<?php

namespace Drupal\users_export;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * User Export Permissions Class.
 */
class UsersExportPermissions {

  use StringTranslationTrait;

  /**
   * Return the permissions defined by this module.
   *
   * @return array
   *   Return perms
   */
  public function permissions() {
    $perms = [];
    if (\Drupal::moduleHandler()->moduleExists('loft_data_grids')) {
      foreach (\Drupal::service('loft_data_grids.core')
        ->getExporters(FALSE) as $info) {
        $perms['users_export:export as ' . $info['id']] = [
          'title'       => $this->t('Export as @name', ['@name' => $info['name']]),
          'description' => Html::escape($info['description']),
        ];
      }
    }

    return $perms;
  }

}

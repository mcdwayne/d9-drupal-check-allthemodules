<?php

namespace Drupal\theme_permission;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Theme Permission.
 *
 * @package Drupal\theme_permission
 */
class ThemePerm {
  use StringTranslationTrait;

  /**
   * Returns an array of permissions.
   *
   * @return array
   *   The permissions.
   */
  public function dynamicPermissions() {
    $perms = [];
    $themes = system_list('theme');
    foreach ($themes as $theme => $value) {
      $type_params = ['%themename' => $theme];
      $perms += [
        "administer themes $theme" => [
          'title' => $this->t('administer themes %themename', $type_params),
        ],
        "uninstall themes $theme" => [
          'title' => $this->t('uninstall themes %themename', $type_params),
        ],
        "Edit Administration theme" => [
          'title' => $this->t('Edit Administration theme'),
        ],
      ];
    }
    return $perms;
  }

}

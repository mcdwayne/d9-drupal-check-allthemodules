<?php

namespace Drupal\moon_phases;

/**
 * Class MoonPhasesPermissions.
 */
class MoonPhasesPermissions {

  /**
   * {@inheritdoc}
   */
  public function permissions() {
    $permissions['administer moon phases'] = [
      'title' => t('Administer the Moon Phase module.'),
    ];

    return $permissions;
  }

}

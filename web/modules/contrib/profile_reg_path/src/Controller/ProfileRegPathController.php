<?php

/**
 * @file
 * Contains
 *   Drupal\multiple_registration\Controller\MultipleRegistrationController.
 */

namespace Drupal\profile_reg_path\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;

class ProfileRegPathController extends ControllerBase {

  /**
   * Gets the title for registration page.
   */
  public function addPageTitle(RouteMatchInterface $route) {
    $type = $route->getRawParameter('type');

    // User profile type label.
    if ($profile_type = static::getProfileTypeByPath($type)) {
      $type = $profile_type->label();
    }

    return $this->t('Create new @role account', array('@role' => $type));
  }

  /**
   * Returns profile type by path argument.
   */
  public static function getProfileTypeByPath($path) {
    $profile_types = \Drupal::entityTypeManager()
      ->getStorage('profile_type')
      ->loadMultiple();

    /** @var \Drupal\profile\Entity\ProfileType $profile_type */
    foreach ($profile_types as $profile_type) {
      $profile_reg_path = $profile_type->getThirdPartySetting('profile_reg_path', 'profile_reg_path');
      if ($profile_reg_path && $profile_reg_path == $path) {
        return $profile_type;
      }
    }

    return NULL;
  }

}

<?php

namespace Drupal\eform;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eform\Entity\EFormType;

/**
 * Defines a class containing permission callbacks.
 */
class EFormPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Gets an array of eform type permissions.
   *
   * @return array
   *    The eform type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function eformTypePermissions() {
    $perms = array();
    // Generate eform permissions for all eform types.
    foreach (EFormType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Builds a standard list of eform permissions for a given type.
   *
   * @param \Drupal\eform\Entity\EFormType $type
   *   The machine name of the eform type.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(EFormType $type) {
    $type_params = array('%type_name' => $type->label());

    return array(
      $type->getPermission('submit') => array(
        'title' => $this->t('%type_name: Submit', $type_params),
      ),
      $type->getPermission('edit own') => array(
        'title' => $this->t('%type_name: Edit own submissions', $type_params),
      ),
      $type->getPermission('delete own') => array(
        'title' => $this->t('%type_name: Delete own submissions', $type_params),
      ),
      // @todo Which other permissions should be supported?
    );
  }

}

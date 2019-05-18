<?php

namespace Drupal\fragments\Permissions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\fragments\Entity\FragmentType;

/**
 * Provides dynamic permissions for fragments of different types.
 */
class FragmentPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of fragment type permissions.
   *
   * @return array
   *   The fragment type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function fragmentTypePermissions() {
    $perms = [];
    // Generate node permissions for all node types.
    foreach (FragmentType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of fragment permissions for a given fragment type.
   *
   * @param \Drupal\fragments\Entity\FragmentType $type
   *   The fragment type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(FragmentType $type) {
    $type_id = $type->id();
    $permissions = [];
    $replacements = ['%type' => $type->label()];

    $permissions[$this->buildPermissionId($type_id, 'create')] = [
      'title' => $this->t('Create %type fragments', $replacements),
      'description' => $this->t(
        'Allows users to create fragments of type %type.',
        $replacements
      ),
      'restrict access' => TRUE,
    ];
    $permissions[$this->buildPermissionId($type_id, 'view')] = [
      'title' => $this->t('View %type fragments', $replacements),
      'description' => $this->t(
        'Allows users to view fragments of type %type. This does not affect whether a user is able to view individual fragments on their own page.',
        $replacements
      ),
    ];
    $permissions[$this->buildPermissionId($type_id, 'update')] = [
      'title' => $this->t('Edit any %type fragment', $replacements),
      'description' => $this->t(
        'Allows users to edit any fragment of type %type.',
        $replacements
      ),
      'restrict access' => TRUE,
    ];
    $permissions[$this->buildPermissionId($type_id, 'update own')] = [
      'title' => $this->t('Edit own %type fragments', $replacements),
      'description' => $this->t(
        'Allows users to edit the fragments of type %type they created.',
        $replacements
      ),
      'restrict access' => TRUE,
    ];
    $permissions[$this->buildPermissionId($type_id, 'delete')] = [
      'title' => $this->t('Delete any %type fragment', $replacements),
      'description' => $this->t(
        'Allows users to delete any fragment of type %type.',
        $replacements
      ),
      'restrict access' => TRUE,
    ];
    $permissions[$this->buildPermissionId($type_id, 'delete own')] = [
      'title' => $this->t('Delete own %type fragment', $replacements),
      'description' => $this->t(
        'Allows users to delete fragments of type %type they created.',
        $replacements
      ),
      'restrict access' => TRUE,
    ];

    return $permissions;
  }

  /**
   * Produce the permission machine name for an operation and a fragment type.
   *
   * @param string $type
   *   The machine name of a fragment type. There is no validity checking on
   *   this.
   * @param string $op
   *   One of 'create', 'view', 'update', 'update own', 'delete' or
   *   'delete own'. (This is not actually checked).
   *
   * @return string
   *   A string used to identify the corresponding permission.
   */
  public static function buildPermissionId($type, $op) {
    return $op . ' ' . $type . ' fragments';
  }

}

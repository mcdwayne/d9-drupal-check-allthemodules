<?php

/**
 * @file
 * Contains \Drupal\comment_perm\CommentPermissions.
 */

namespace Drupal\comment_perm;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\comment\Entity\CommentType;

/**
 * Provide dynamic permissions for comments of different types.
 */
class CommentPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of comment type permissions.
   *
   * @return array
   *   The comment type permissions
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function commentTypePermissions() {
    $perm = array();
    // Generate comment permissions for all comment types.
    foreach (CommentType::loadMultiple() as $type) {
      $perm += $this->buildPermissions($type);
    }

    return $perm;
  }

  /**
   * Returns a list of node permissions for a given node type.
   *
   * @param \Drupal\comment\Entity\CommentType $type
   *   The comment type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(CommentType $type) {
    $type_id = $type->id();
    $type_params = array('%type_name' => $type->label());

    return array(
      "administer $type_id comments" => array(
        'title' => $this->t('%type_name: Administer comments and comment settings', $type_params),
      ),
      "administer $type_id comment type" => array(
        'title' => $this->t('%type_name: Administer comment type and settings', $type_params),
        'restrict access' => TRUE,
      ),
      "access $type_id comments" => array(
        'title' => $this->t('%type_name: View comments', $type_params),
      ),
      "post $type_id comments" => array(
        'title' => $this->t('%type_name: Post comments', $type_params),
      ),
      "skip $type_id comment approval" => array(
        'title' => $this->t('%type_name: Skip comment approval', $type_params),
      ),
      "edit $type_id own comments" => array(
        'title' => $this->t('%type_name: Edit own comments', $type_params),
      ),
    );
  }

}

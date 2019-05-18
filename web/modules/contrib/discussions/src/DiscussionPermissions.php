<?php

namespace Drupal\discussions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\discussions\Entity\DiscussionType;

/**
 * Permissions for Discussions.
 */
class DiscussionPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of Discussion Type permissions.
   *
   * @return array
   *   Returns an array of permissions.
   */
  public function discussionTypePermissions() {
    $perms = [];
    // Generate Discussion permissions for all Discussion Types.
    foreach (DiscussionType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Builds a standard list of permissions for a given Discussion Type.
   *
   * @param \Drupal\discussions\Entity\DiscussionType $discussion_type
   *   The machine name of the Discussion Type.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(DiscussionType $discussion_type) {
    $type_id = $discussion_type->id();
    $type_params = ['%type' => $discussion_type->label()];

    return [
      "view $type_id discussion" => [
        'title' => $this->t('%type: View discussion', $type_params),
      ],
      "create $type_id discussion" => [
        'title' => $this->t('%type: Create new discussion', $type_params),
      ],
      "edit own $type_id discussion" => [
        'title' => $this->t('%type: Edit own discussion', $type_params),
      ],
      "edit any $type_id discussion" => [
        'title' => $this->t('%type: Edit any discussion', $type_params),
      ],
      "delete own $type_id discussion" => [
        'title' => $this->t('%type: Delete own discussion', $type_params),
      ],
      "delete any $type_id discussion" => [
        'title' => $this->t('%type: Delete any discussion', $type_params),
      ],
      "reply to own $type_id discussion" => [
        'title' => $this->t('%type: Reply to own discussion', $type_params),
      ],
      "reply to any $type_id discussion" => [
        'title' => $this->t('%type: Reply to any discussion', $type_params),
      ],
    ];
  }

}

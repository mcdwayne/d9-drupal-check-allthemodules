<?php

namespace Drupal\node_view_published_override;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Class definition.
 *
 * @category NodeViewPublishedOverridePermissions
 *
 * @package Access Control
 */
class NodeViewPublishedOverridePermissions {
  use StringTranslationTrait;

  /**
   * Permission function.
   *
   * Added the permissions.
   */
  public function permissions() {
    $permissions = [];
    $nodeTypes = NodeType::loadMultiple();
    foreach ($nodeTypes as $nodeType) {
      $permission = 'view own published ' . $nodeType->id() . ' content';
      $permissions[$permission] = [
        'title' => $this->t('<em>@type_label</em>: view own published content', ['@type_label' => $nodeType->label()]),
      ];
      $permission = 'view any published ' . $nodeType->id() . ' content';
      $permissions[$permission] = [
        'title' => $this->t('<em>@type_label</em>: view any published content', ['@type_label' => $nodeType->label()]),
      ];
    }
    return $permissions;
  }

}

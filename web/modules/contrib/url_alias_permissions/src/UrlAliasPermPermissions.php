<?php

namespace Drupal\url_alias_permissions;

use Drupal\node\Entity\NodeType;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class UrlAliasPermPermissions.
 */
class UrlAliasPermPermissions {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function permissions() {
    $permissions = [];

    // Generate edit url alias permissions for all content types.
    foreach (NodeType::loadMultiple() as $node_type) {
      $permissions += [
        'edit ' . $node_type->id() . ' URL alias' => [
          'title' => $this->t('Create and edit %type_name URL alias', ['%type_name' => $node_type->label()]),
        ],
      ];
    }

    return $permissions;
  }

}

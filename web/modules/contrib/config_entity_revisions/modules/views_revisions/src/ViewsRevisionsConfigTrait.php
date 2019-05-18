<?php

namespace Drupal\views_revisions;

use Drupal\config_entity_revisions\ConfigEntityRevisionsStorageTrait;


trait ViewsRevisionsConfigTrait {
  use ConfigEntityRevisionsStorageTrait;

  private $constants = [
    'module_name' => 'views_revisions',
    'config_entity_name' => 'view',
    'revisions_entity_name' => 'ViewsRevisions',
    'setting_name' => 'views_revisions_id',
    'title' => 'Views',
    'has_own_content' => FALSE,
    'admin_permission' => 'administer views',
    'has_canonical_url' => FALSE,
  ];

  /**
   * Get the entity that actually has revisions.
   */
  public function revisioned_entity() {
    return $this->get('storage');
  }
}
<?php

/**
 * @file
 * Definition of Drupal\entityreference\Plugin\entityreference\selection\SelectionEntityTypeNode.
 *
 * Provide entity type specific access control of the node entity type.
 */

namespace Drupal\entityreference\Plugin\Type\Selection;

use Drupal\Core\Entity\EntityFieldQuery;
use Drupal\Core\Database\Query\AlterableInterface;

use Drupal\entityreference\Plugin\entityreference\selection\SelectionBase;

class SelectionEntityTypeNode extends SelectionBase {

  public function entityFieldQueryAlter(AlterableInterface $query) {
    // Adding the 'node_access' tag is sadly insufficient for nodes: core
    // requires us to also know about the concept of 'published' and
    // 'unpublished'. We need to do that as long as there are no access control
    // modules in use on the site. As long as one access control module is there,
    // it is supposed to handle this check.
    if (!user_access('bypass node access') && !count(module_implements('node_grants'))) {
      $tables = $query->getTables();
      $query->condition(key($tables) . '.status', NODE_PUBLISHED);
    }
  }
}
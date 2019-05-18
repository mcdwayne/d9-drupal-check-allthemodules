<?php

/**
 * @file
 * Definition of Drupal\entityreference\Plugin\entityreference\selection\SelectionEntityTypeComment.
 *
 * Provide entity type specific access control of the node entity type.
 */

namespace Drupal\entityreference\Plugin\Type\Selection;

use Drupal\Core\Entity\EntityFieldQuery;
use Drupal\Core\Database\Query\AlterableInterface;

use Drupal\entityreference\Plugin\entityreference\selection\SelectionBase;

class SelectionEntityTypeComment extends SelectionBase {

  public function entityFieldQueryAlter(AlterableInterface $query) {
    // Adding the 'comment_access' tag is sadly insufficient for comments: core
    // requires us to also know about the concept of 'published' and
    // 'unpublished'.
    if (!user_access('administer comments')) {
      $tables = $query->getTables();
      $query->condition(key($tables) . '.status', COMMENT_PUBLISHED);
    }

    // The Comment module doesn't implement any proper comment access,
    // and as a consequence doesn't make sure that comments cannot be viewed
    // when the user doesn't have access to the node.
    $tables = $query->getTables();
    $base_table = key($tables);
    $node_alias = $query->innerJoin('node', 'n', '%alias.nid = ' . $base_table . '.nid');
    // Pass the query to the node access control.
    $this->reAlterQuery($query, 'node_access', $node_alias);

    // Alas, the comment entity exposes a bundle, but doesn't have a bundle column
    // in the database. We have to alter the query ourself to go fetch the
    // bundle.
    $conditions = &$query->conditions();
    foreach ($conditions as $key => &$condition) {
      if ($key !== '#conjunction' && is_string($condition['field']) && $condition['field'] === 'node_type') {
        $condition['field'] = $node_alias . '.type';
        foreach ($condition['value'] as &$value) {
          if (substr($value, 0, 13) == 'comment_node_') {
            $value = substr($value, 13);
          }
        }
        break;
      }
    }

    // Passing the query to node_query_node_access_alter() is sadly
    // insufficient for nodes.
    // @see EntityReferenceHandler_node::entityFieldQueryAlter()
    if (!user_access('bypass node access') && !count(module_implements('node_grants'))) {
      $query->condition($node_alias . '.status', 1);
    }
  }
}

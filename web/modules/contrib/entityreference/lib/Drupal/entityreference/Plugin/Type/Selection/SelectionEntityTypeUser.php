<?php

/**
 * @file
 * Definition of Drupal\entityreference\Plugin\entityreference\selection\SelectionEntityTypeNode.
 *
 * Provide entity type specific access control of the node entity type.
 */

namespace Drupal\entityreference\Plugin\Type\Selection;

use Drupal\Core\Entity\EntityFieldQuery;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\AlterableInterface;


use Drupal\entityreference\Plugin\entityreference\selection\SelectionBase;

class SelectionEntityTypeUser extends SelectionBase {

  public function buildEntityFieldQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityFieldQuery($match, $match_operator);

    // The user entity doesn't have a label column.
    if (isset($match)) {
      $query->propertyCondition('name', $match, $match_operator);
    }

    // Adding the 'user_access' tag is sadly insufficient for users: core
    // requires us to also know about the concept of 'blocked' and
    // 'active'.
    if (!user_access('administer users')) {
      $query->propertyCondition('status', 1);
    }
    return $query;
  }

  public function entityFieldQueryAlter(AlterableInterface $query) {
    if (user_access('administer users')) {
      // In addition, if the user is administrator, we need to make sure to
      // match the anonymous user, that doesn't actually have a name in the
      // database.
      $conditions = &$query->conditions();
      foreach ($conditions as $key => $condition) {
        if ($key !== '#conjunction' && is_string($condition['field']) && $condition['field'] === 'users.name') {
          // Remove the condition.
          unset($conditions[$key]);

          // Re-add the condition and a condition on uid = 0 so that we end up
          // with a query in the form:
          //    WHERE (name LIKE :name) OR (:anonymous_name LIKE :name AND uid = 0)
          $or = db_or();
          $or->condition($condition['field'], $condition['value'], $condition['operator']);
          // Sadly, the Database layer doesn't allow us to build a condition
          // in the form ':placeholder = :placeholder2', because the 'field'
          // part of a condition is always escaped.
          // As a (cheap) workaround, we separately build a condition with no
          // field, and concatenate the field and the condition separately.
          $value_part = db_and();
          $value_part->condition('anonymous_name', $condition['value'], $condition['operator']);
          $value_part->compile(Database::getConnection(), $query);
          $or->condition(db_and()
            ->where(str_replace('anonymous_name', ':anonymous_name', (string) $value_part), $value_part->arguments() + array(':anonymous_name' => user_format_name(user_load(0))))
            ->condition('users.uid', 0)
          );
          $query->condition($or);
        }
      }
    }
  }
}
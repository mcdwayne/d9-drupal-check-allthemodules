<?php

namespace Drupal\private_messages\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\user\Entity\User;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "private_messages:user",
 *   label = @Translation("Private Messages User selection"),
 *   entity_types = {"user"},
 *   group = "default",
 *   weight = 1
 * )
 */
class UserSelection extends \Drupal\user\Plugin\EntityReferenceSelection\UserSelection {
  /**
   * {@inheritdoc}
   *
   * @TODO: Refactor this somehow. Solution is ugly. Plain queries is evil!
   */
  public function entityQueryAlter(SelectInterface $query) {
    /// Ugly part starts.
    $account = $this->currentUser->getAccount();
    $current_user = User::load($account->id());
    $blocked_users = $current_user->get('field_blocked_user')->getValue();

    $ids = [];
    foreach($blocked_users as $k => $v) {
      $ids[] = $v['target_id'];
    }

    // Bail out early if we do not need to match the Anonymous user.
    $handler_settings = $this->configuration['handler_settings'];

    $query->leftJoin('user__field_blocked_user','fb','fb.entity_id = base_table.uid');
    if(!empty($ids)) {
      $query->condition('base_table.uid', $ids, 'NOT IN');
    }
    $query->condition('base_table.uid',$this->currentUser->id(),'<>');
    /// Ugly part ends.

    if (isset($handler_settings['include_anonymous']) && !$handler_settings['include_anonymous']) {
      return;
    }

    if ($this->currentUser->hasPermission('administer users')) {
      // In addition, if the user is administrator, we need to make sure to
      // match the anonymous user, that doesn't actually have a name in the
      // database.
      $conditions = &$query->conditions();
      foreach ($conditions as $key => $condition) {
        if ($key !== '#conjunction' && is_string($condition['field']) && $condition['field'] === 'users_field_data.name') {
          // Remove the condition.
          unset($conditions[$key]);

          // Re-add the condition and a condition on uid = 0 so that we end up
          // with a query in the form:
          // WHERE (name LIKE :name) OR (:anonymous_name LIKE :name AND uid = 0)
          $or = db_or();
          $or->condition($condition['field'], $condition['value'], $condition['operator']);
          // Sadly, the Database layer doesn't allow us to build a condition
          // in the form ':placeholder = :placeholder2', because the 'field'
          // part of a condition is always escaped.
          // As a (cheap) workaround, we separately build a condition with no
          // field, and concatenate the field and the condition separately.
          $value_part = db_and();
          $value_part->condition('anonymous_name', $condition['value'], $condition['operator']);
          $value_part->compile($this->connection, $query);
          $or->condition(db_and()
            ->where(str_replace('anonymous_name', ':anonymous_name', (string) $value_part), $value_part->arguments() + [':anonymous_name' => \Drupal::config('user.settings')->get('anonymous')])
            ->condition('base_table.uid', 0)
          );
          $query->condition($or);
        }
      }
    }
  }

}

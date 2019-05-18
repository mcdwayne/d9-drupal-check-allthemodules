<?php

namespace Drupal\rules_user_fields\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;

/**
 * Action for getting access to all fields of a User entity.
 *
 * @RulesAction(
 *   id = "get_access_to_user_fields",
 *   label = @Translation("Get access to user fields"),
 *   category = @Translation("User"),
 *   context = {
 *     "user" = @ContextDefinition("entity",
 *       label = @Translation("User"),
 *       required = TRUE,
 *       assignment_restriction = "selector"
 *     )
 *   }
 * )
 */
class GetAccessToUserFields extends RulesActionBase {

  /**
   * Do nothing.
   */
  protected function doExecute() {
  }

  /**
   * {@inheritdoc}
   */
  public function assertMetadata(array $selected_data) {
    $changed_definitions = [];
    if (isset($selected_data['user'])) {
      $changed_definitions['user'] = clone $selected_data['user'];
      $changed_definitions['user']->setBundles(['user']);
    }
    return $changed_definitions;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\user_badges\Plugin\RulesAction\AssignBadge.
 */

namespace Drupal\user_badges\Plugin\RulesAction;

use Drupal\Core\Session\AccountInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'AssignBadge Badge' action.
 *
 * @RulesAction(
 *  id = "assign_badge",
 *  label = @Translation("Assign badge"),
 *  type = "user",
 *  category = @Translation("User Badges"),
 *  context = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User")
 *     ),
 *     "roles" = @ContextDefinition("entity:badge",
 *       label = @Translation("Badges"),
 *       multiple = TRUE
 *     )
 *   }
 * )
 */
class AssignBadge extends RulesActionBase {
  /**
   * {@inheritdoc}
   */
  public function doexecute(UserInterface $account, array $badges) {
    // Insert code here.
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}

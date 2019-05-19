<?php

/**
 * @file
 * Contains \Drupal\user_badges\Plugin\RulesAction\RemoveBadge.
 */

namespace Drupal\user_badges\Plugin\RulesAction;

use Drupal\Core\Session\AccountInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'RemoveBadge' action.
 *
 * @RulesAction(
 *  id = "remove_badge",
 *  label = @Translation("Remove badge"),
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
 *   },
 * )
 */
class RemoveBadge extends RulesActionBase {
  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
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

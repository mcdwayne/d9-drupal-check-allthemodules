<?php

/**
 * @file
 * Hooks provided by the Flag conditional confirm module.
 */

/**
 * @addtogroup hooks
 * @{
 */

use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Control when the confirm form is used for flagging.
 *
 * This example will only require confirmation if the user is trying to unflag
 * something currently flagged that has a value set for a field on the flag
 * entity called "field_important_field".
 *
 * @param string $action
 *   The flagging action (flag|unflag).
 * @param \Drupal\flag\FlagInterface $flag
 *   The flag entity.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity being flagged/unflagged.
 * @param mixed $flagging
 *   The flagging object (or NULL if not flagged).
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The current user.
 *
 * @return bool
 *   TRUE if the confirm form should be shown.
 */
function hook_flag_conditional_confirm_confirmation_required($action, FlagInterface $flag, EntityInterface $entity, $flagging, AccountInterface $account) {
  if ($action != 'flag') {
    /** @var \Drupal\flag\FlaggingInterface $flagging */
    if ($flagging && $flagging->field_important_field->value) {
      return TRUE;
    }
  }
  return FALSE;
}

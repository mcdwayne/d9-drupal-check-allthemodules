<?php

/**
 * @file
 * Contains API documentation and examples for the Paragraphs access module.
 */

use Drupal\paragraphs\ParagraphInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the message that is displayed for paragraphs the user can't edit.
 *
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   Item that the users denied access to.
 * @param string $operation
 *   Entity Operation for grants to apply to. one of edit, update, delete.
 * @param array $message
 *   Current render element markup.
 */
function hook_paragraphs_access_restriction_message_alter(ParagraphInterface $paragraph, $operation, array &$message) {
  // Set message on blocked items to something polite.
  $message["#markup"] = "Sorry, You may not " . $operation . " this item.";
}

/**
 * @}
 */

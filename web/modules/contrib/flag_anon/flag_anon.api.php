<?php

/**
 * @file
 * Hooks provided by the Flag anonymous module.
 */

use Drupal\flag\FlagInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter anonymous message placeholders list.
 *
 * @param array $placeholders
 *   Placeholders list, usage in FormattableMarkup as arguments.
 * @param \Drupal\flag\FlagInterface $flag
 *   The Flag entity.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The flaggable entity.
 */
function hook_flag_anon_message_placeholders_alter(array &$placeholders, FlagInterface $flag, EntityInterface $entity) {

}

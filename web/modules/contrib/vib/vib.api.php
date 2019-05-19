<?php

/**
 * @file
 * Hooks related to the "View in Browser" module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the lifetime options list values keyed by count of seconds.
 *
 * @param array $list
 *   An array options.
 */
function hook_vib_lifetime_options_alter(array &$list) {
  $list['3600'] = t('1 hour');
}

/**
 * @} End of "addtogroup hooks".
 */

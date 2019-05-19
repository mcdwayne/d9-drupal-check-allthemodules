<?php

/**
 * @file
 * Hooks related to User Sanitize module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the list of roles which get excluded from sanitization.
 *
 * @param array $excluded_roles
 *   The array of excluded roles.
 */
function hook_user_sanitize_excluded_roles_alter(array &$excluded_roles) {

}

/**
 * Alter the list of user fields which get excluded from sanitization.
 *
 * @param array $excluded_fields
 *   The array of excluded fields.
 */
function hook_user_sanitize_excluded_user_fields_alter(array &$excluded_fields) {

}

/**
 * @} End of "addtogroup hooks".
 */

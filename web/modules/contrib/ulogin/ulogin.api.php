<?php

/**
 * @file
 * Hooks provided by the uLogin module.
 */

/**
 * Alter the username for a user before creation.
 *
 * @param $name string
 *   The username for the user being created, it must be unique.
 * @param $data object
 *   The data object with all the properties from authentication provider.
 *
 * @see \Drupal\ulogin\UloginHelper::makeUsername()
 *
 * @ingroup ulogin
 */
function hook_ulogin_username_alter(&$name, $data) {

}

/**
 * @} End of "addtogroup hooks".
 */

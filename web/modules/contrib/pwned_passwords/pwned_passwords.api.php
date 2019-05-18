<?php

/**
 * Allows adding (or removing) the option to validate a form.
 * Note that the forms must still be enabled for validation at /admin/config/people/pwnedpassword
 *
 * @param array $possible_forms
 *   Default forms available from the pwned_passwords module
 *
 * @return array
 *   Must return a array/map with form_id as keys and a human readable name as value.
 */
function hook_pwned_check_form_options_alter(array &$available_forms): array {

}
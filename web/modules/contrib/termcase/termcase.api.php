<?php

/**
 * @file
 * Hooks specific to the Termcase module.
 */

/**
 * Alter string conversion before it is saved.
 *
 * @param string $converted_string
 *   The string that is about to be saved after the conversion.
 *
 * @param string $original_string
 *   The original unaltered string.
 *
 * @param int $case
 *   Static that defines the given case formatting. Possible options:
 *     TERMCASE_NONE
 *     TERMCASE_UCFIRST
 *     TERMCASE_LOWERCASE
 *     TERMCASE_UPPERCASE
 *     TERMCASE_PROPERCASE
 */
function hook_termcase_convert_string_alter(&$converted_string, $original_string, $case) {
  $converted_string = str_replace(' ', '', $converted_string);
}

<?php

/**
 * @file
 * Implements hooks to update the module.
 *
 * Note that comments on update functions are parsed and shown to the
 * site admin to describe an update before it is applied.
 */

/*----------------------------------------------------------------------
 *
 * 0.2.1 to 0.3.0
 *
 *----------------------------------------------------------------------*/

/**
 * Update field formatters.
 *
 * This mandatory update flushes all caches in order to pick up changes
 * in formatter definitions and templates. Note that some formatters have
 * been deprecated, though they will still work.
 */
function formatter_suite_update_8001() {
  // This update is intentionally empty. Its effect is to cause all
  // caches to be flushed.
  return t('Update complete.');
}

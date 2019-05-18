<?php

/**
 * @file
 * Hooks provided by the Inline module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Inform the Inline API about macro types/tags.
 *
 * @return array
 *   An associative array of inline macro implementations provided by a module,
 *   keyed by type/tag name, using the following properties:
 *   - class: The fully qualified class name of the macro implementation.
 */
function hook_inline_info() {
  $info['iframe'] = array(
    'class' => 'Drupal\iframe\IframeMacro',
  );
  return $info;
}


<?php

/**
 * @file
 * Hooks and documentation related to paragraphs_sets module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the default field data provided by all sets.
 *
 * @param array $data
 *   Default field values for the paragraph bundles in the set.
 * @param array $context
 *   An associative array containing the following key-value pairs:
 *   - field: Name of field currently operated on.
 *   - form: The form render array.
 *   - form_state: The current form state.
 *   - key: Internal key of paragraph in set.
 *   - paragraphs_bundle: Bundle name of paragraph.
 *   - set: Machine name of current set.
 */
function hook_paragraphs_set_data_alter(array &$data, array $context) {
}

/**
 * Alter the default field data provided by a specific set.
 *
 * @param array $data
 *   Default field values for the paragraph bundles in the set.
 * @param array $context
 *   An associative array containing the following key-value pairs:
 *   - field: Name of field currently operated on.
 *   - form: The form render array.
 *   - form_state: The current form state.
 *   - key: Internal key of paragraph in set.
 *   - paragraphs_bundle: Bundle name of paragraph.
 *   - set: Machine name of current set.
 */
function hook_paragraphs_set_SET_data_alter(array &$data, array $context) {
}

/**
 * Alter the default field data provided by a specific set for a single field.
 *
 * @param array $data
 *   Default field values for the paragraph bundles in the set.
 * @param array $context
 *   An associative array containing the following key-value pairs:
 *   - field: Name of field currently operated on.
 *   - form: The form render array.
 *   - form_state: The current form state.
 *   - key: Internal key of paragraph in set.
 *   - paragraphs_bundle: Bundle name of paragraph.
 *   - set: Machine name of current set.
 */
function hook_paragraphs_set_SET_FIELD_NAME_data_alter(array &$data, array $context) {
}

/**
 * @} End of "addtogroup hooks".
 */

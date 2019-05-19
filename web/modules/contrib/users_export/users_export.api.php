<?php

/**
 * @file
 * API documentation for users_export module.
 *
 * @ingroup users_export
 */

/**
 * Implements hook_users_export_row_alter().
 *
 * Modify the contents of an exported row.
 *
 * @codingStandardsIgnoreStart
 *
 * @param array &$row
 *   Row.
 * @param int $uid
 *   UID.
 * @param array $context
 *   Content.
 *   - settings array The form_state values coming from the submission form.
 *
 * @return null.
 *
 * @codingStandardsIgnoreEnd
 */
function hook_users_export_row_alter(array &$row, $uid, array &$context) {
  // This uses more memory but less database calls and should be faster.
  if (empty($context['sandbox'][$uid])) {
    $query = \Drupal::database()->select('user__field_first_name', 'fn')
      ->condition('fn.entity_id', $context['settings']['uids'], 'IN');
    $query->addField('fn', 'entity_id', 'uid');
    $query->addField('fn', 'field_first_name_value', 'first');
    $query->addField('ln', 'field_last_name_value', 'last');
    $query->leftJoin('user__field_last_name', 'ln', 'fn.entity_id = ln.entity_id');
    $context['sandbox'] = $query->execute()->fetchAllAssoc('uid');

    //
    //
    // Support for roles as columns with x's.
    //
    // If this is not needed, I would not include it as it will slow things down
    // and increase the memory footprint.
    //
    $valueTrue = 'x';
    $valueFalse = '';
    $query = \Drupal::database()->select('user__roles', 'r')
      ->condition('r.entity_id', $context['settings']['uids'], 'IN');
    $query->addField('r', 'roles_target_id', 'role');
    $query->addField('r', 'entity_id', 'uid');
    $result = $query->execute();
    $roles = [];
    $roleIds = [];
    while ($record = $result->fetchObject()) {
      $roleIds[$record->role] = $record->role;
      $roles[$record->uid][$record->role] = TRUE;
    }
    foreach ($context['sandbox'] as $uid => $data) {
      foreach ($roleIds as $roleId) {
        $context['sandbox'][$uid]->{$roleId} = isset($roles[$uid][$roleId]) ? $valueTrue : $valueFalse;
      }
    }
  }

  unset($row['uuid']);
  if (isset($context['sandbox'][$uid])) {
    $row += (array) $context['sandbox'][$uid];
  }
}

/**
 * Implements hook_users_export_exporter_alter().
 *
 * @codingStandardsIgnoreStart
 *
 * @param ExporterInterface $exporter
 *   Exporter.
 *
 * @codingStandardsIgnoreEnd
 */
function hook_users_export_exporter_alter(ExporterInterface $exporter) {

  // Example shows how we can reverse the order of the columns.
  $keys = $exporter->getData()->getKeys();
  $keys = array_reverse($keys);
  $exporter->getData()->setKeys($keys);
}

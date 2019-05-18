<?php

/**
 * @file
 *   Example drush command.
 *
 *   To run this *fun* command, execute `sudo drush --include=./examples mmas`
 *   from within your drush directory.
 *
 *   See `drush topic docs-commands` for more information about command authoring.
 *
 *   You can copy this file to any of the following
 *     1. A .drush folder in your HOME folder.
 *     2. Anywhere in a folder tree below an active module on your site.
 *     3. /usr/share/drush/commands (configurable)
 *     4. In an arbitrary folder specified with the --include option.
 *     5. Drupal's /drush or /sites/all/drush folders.
 */

use Drupal\Core\Database\DatabaseException;

/**
 * Implementation of hook_drush_command().
 */
function monkey_drush_command() {
  $items = array();

  // The hard-working 'monkey-check' command
  $items['monkey-check'] = array(
    'description' => "Hard-working Monkey do some checks of your running Drupal site.",
    'arguments' => array(
      'check' => 'The type of the check (fields, etc.). Defaults to fields.',
      'entity_type' => 'The entity type to check.',
      'bundle' => 'The bundle to check.'
    ),
    'options' => array(
      'fields' => array(
        'description' => 'Comma delimited list of field machine names. Optionally specifying column to be checked for emptiness, separated with a dot.',
        'example-value' => 'field_body,field_example.column',
      ),
    ),
    'examples' => array(
      'drush monkey-checks fields --fields=field_body ' => 'Make a terrible-tasting sandwich that is lacking in pickles.',
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL, // Full bootstrap for required Drupal functions.
  );

  // The hard-working 'monkey-fix' command where our monkey try to fix fields, etc.
  $items['monkey-fix'] = array(
    'description' => "Hard-working Monkey fix something in your running Drupal site.",
    'arguments' => array(
      'fix' => 'The type of the fix (fields, etc.). Defaults to fields.',
      'entity_type' => 'The entity type to check.',
      'bundle' => 'The bundle to check.'
    ),
    'options' => array(
      'fields' => array(
        'description' => 'Comma delimited list of field machine names. Optionally specifying column to be checked for emptiness, separated with a dot.',
        'example-value' => 'field_body,field_example.column',
      ),
      'fast' => array(
        'description' => 'With this option hard-working monkey do his jobs faster (experimental).',
      ),
      'with-batch' => array(
        'description' => 'With this option hard-working monkey do his jobs as batch operations.',
      ),
      'batch-items' => array(
        'description' => 'With this option you can tell hard-working monkey how many items during a batch operation should being fixed.',
      ),
    ),
    'examples' => array(
      'drush monkey-fix fields node article --fields=field_xyz' => 'Monkey try to fix the field_xyz field for article node entities.',
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL, // Full bootstrap for required Drupal functions.
  );

  return $items;
}

/**
 * Implementation of hook_drush_help().
 */
function monkey_drush_help($section) {
  switch ($section) {
    case 'drush:monkey-check':
      return dt("This command will make some checks by hard-working monkey.");
    case 'drush:monkey-fix':
      return dt("This command will make some fixes by hard-working monkey.");
  }
}

/**
 * Implementation of hook_drush_command() for monkey-check command.
 */
function drush_monkey_check($check = 'fields', $entity_type = 'node', $bundle = 'page') {

  $instances = _monkey_get_instances($entity_type, $bundle);
  $bundle_name = _monkey_get_bundle($entity_type);
  $fields = array_keys($instances);

  $msg = dt('Monkey checks following !check: !fields.',
    array('!check' => $check, '!fields' => implode(', ', $fields))
  );
  drush_print($msg);

  $query = \Drupal::entityQuery($entity_type)
    ->condition($bundle_name, $bundle);
  $result = $query->execute();

  $msg = dt('Monkey checks for !count !bundle !entity_type(s).',
    array(
      '!count' => count($result),
      '!bundle' => $bundle,
      '!entity_type' => $entity_type,
    )
  );
  drush_print("\n" . $msg . "\n");

  foreach ($fields as $field) {
    $have_value_count = dt('Unknown field condition.');

    try {

      $query = \Drupal::entityQuery($entity_type)
        ->condition($bundle_name, $bundle)
        ->notExists($field);
      $result1 = $query->execute();

      $have_value_count = count($result) - count($result1);

      $empty_default_count = dt('This field has no default value.');
      $default = $instances[$field]->getDefaultValueLiteral();
      if (!empty($default)) {
        $all_values = array_keys($result);
        $no_values = array_keys($result1);

        $entity_have_values = array_diff($all_values, $no_values);

        $empty_default_count = count($no_values);
      }
    } catch (DatabaseException $e) {
      $query = 'SQLSTATE[42S22]: Column not found';
      if (substr($e->getMessage(), 0, strlen($query)) === $query) {
        echo "\nThere was an error while querying for the existence of a value for field: " . $field . "\n";
        echo "Try to specify the column of the field, used for checking emptiness.\n\n";
      }
    } catch (Exception $e) {
    }

    $checked_field = dt('Field: !field',
      array('!field' => $field)
    );
    $have_value = dt('Has value(s): !have_value_count',
      array('!have_value_count' => $have_value_count)
    );
    $empty_default = dt('Without default: !empty_default_count',
      array('!empty_default_count' => $empty_default_count)
    );
    drush_print($checked_field . "\n" . $have_value . "\n" . $empty_default . "\n");
  }
}

/**
 * Implementation of hook_drush_command() for monkey-fix command.
 */
function drush_monkey_fix($fix = 'fields', $entity_type = 'node', $bundle = 'page') {

  $instances = _monkey_get_instances($entity_type, $bundle);
  $bundle_name = _monkey_get_bundle($entity_type);
  $fields = array_keys($instances);

  $msg = dt('Monkey try to fix following !fix: !fields.',
    array('!fix' => $fix, '!fields' => implode(', ', $fields))
  );
  drush_print($msg . "\n");

  $query = \Drupal::entityQuery($entity_type)
    ->condition($bundle_name, $bundle);
  $result = $query->execute();

  foreach ($fields as $field) {
    $have_value_count = dt('Unknown field condition.');

    list($field_name, $field_column) = explode('.', $field);

    $fix_entitys = array();
    try {

      $query = \Drupal::entityQuery($entity_type)
        ->condition($bundle_name, $bundle)
        ->notExists($field);
      $result1 = $query->execute();

      $have_value_count = count($result) - count($result1);

      $empty_default_count = dt('This field has no default value.');
      $default = $instances[$field]->getDefaultValueLiteral();
      if (!empty($default)) {
        $all_values = array_keys($result);
        $no_values = array_keys($result1);

        $entity_have_values = array_diff($all_values, $no_values);

        $empty_default_count = count($no_values);

        if ($empty_default_count > 0) {
          $fix_entitys = $result1;
        }
      }
    } catch (DatabaseException $e) {
      $query = 'SQLSTATE[42S22]: Column not found';
      if (substr($e->getMessage(), 0, strlen($query)) === $query) {
        echo "\nThere was an error while querying for the existence of a value for field: " . $field . "\n";
        echo "Try to specify the column of the field, used for checking emptiness.\n\n";
      }
    } catch (Exception $e) {
    }

    $checked_field = dt('Field: !field',
      array('!field' => $field)
    );
    $have_value = dt('Has value(s): !have_value_count',
      array('!have_value_count' => $have_value_count)
    );
    $empty_default = dt('Without default: !empty_default_count',
      array('!empty_default_count' => $empty_default_count)
    );
    if (!count($fix_entitys)) {
      drush_print("No entities selected.\n");
      return;
    }
    else {
      drush_print($checked_field . "\n" . $have_value . "\n" . $empty_default . "\n");
    }

    $fixed_count = 0;
    $with_batch = (bool) drush_get_option('with-batch');

    if ($with_batch) {

      $batch_items = (int) drush_get_option('batch-items');
      if ($batch_items == 0 || !is_int($batch_items) ) {
        $batch_items = 100;
      }

      $drush_options = array(
        'batch_items' => $batch_items,
      );

      $batch = array(
        'title' => t('Monkey drush batch process to fix entities'),
        'operations' => array(
          array(
            'monkey_drush_batch_fix_operation',
            array(
              $entity_type,
              $fix_entitys,
              $field,
              $drush_options,
              $instances
            ),
          )
        ),
        'finished' => 'monkey_drush_batch_finished_batch',
      );

      batch_set($batch);
      $batch =& batch_get();
      $batch['progressive'] = FALSE;

      drush_backend_batch_process();
    }
    else {
      $field_instance = $instances[$field];
      foreach($fix_entitys as $id => $entity) {
        $entity = entity_load($entity_type, $entity);
        $field_default = $field_instance->getDefaultValue($entity);
        $entity->set($field_name, $field_default);
        $entity->save();

        $fixed_count++;
        _monkey_drush_print_progress($fixed_count / count($fix_entitys));
      }

      $fixed = dt('Fixed: !fixed_count',
        array('!fixed_count' => $fixed_count)
      );
      drush_print($fixed . "\n");
    }
  }
}

/**
 *  Batch callback for fix operation.
 */
function monkey_drush_batch_fix_operation($entity_type, $fix_entitys, $field, $drush_options = array(), $instances, &$context) {
  list($field_name, $field_column) = explode('.', $field);
  // Init $context['sandbox'] variables.
  if (empty($context['sandbox'])) {

    $context['sandbox']['batch_items'] = $drush_options['batch_items'];

    $context['sandbox']['entitys'] = $fix_entitys;
    $context['sandbox']['max'] = count($fix_entitys);
    $context['sandbox']['progress'] = 0;
  }

  $limit = min($context['sandbox']['batch_items'], count($context['sandbox']['entitys']));

  $field_instance = $instances[$field];
  for ($i = 0; $i < $limit; $i++) {
    $current_entity = array_shift($context['sandbox']['entitys']);
    $entity = entity_load($entity_type, $current_entity);
    $field_default = $field_instance->getDefaultValue($entity);;
    $entity->set($field_name, $field_default);
    $entity->save();

    $fixed_count++;

    $context['results'][] = 'Successfully fixed ' . $field . ' of entity.';

    $context['sandbox']['progress']++;
  }

  _monkey_drush_print_progress($context['sandbox']['progress'] / $context['sandbox']['max']);

  //check if batch is finished and update progress
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

function monkey_drush_batch_finished_batch($success, $results, $operations) {

  if ($success) {

    $fixed = dt('Fixed: !fixed_count',
      array('!fixed_count' => count($results))
    );
    drush_print($fixed . "\n");
  }
}

function _monkey_get_bundle($entity_type) {
  return \Drupal::entityManager()->getDefinition($entity_type)->get('entity_keys')['bundle'];
}

function _monkey_get_instances($entity_type, $bundle) {

  $all_instances = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);

  // Read options with drush_get_option. Note that the options _must_
  // be documented in the $items structure for this command in the 'command' hook.
  // See `drush topic docs-commands` for more information.
  if ($fields = drush_get_option('fields')) {

    $fields = explode(',', $fields);

    foreach ($fields as $field) {
      // Allow column to be specified with a dot.
      list($field_name, $field_value) = explode('.', $field);
      if (!empty($all_instances[$field_name])) {
        $instances[$field] = $all_instances[$field_name];
      }
    }
  }
  else {
    $instances = $all_instances;
  }

  return $instances;
}

/**
 * Provides progress bar.
 */
function _monkey_drush_print_progress($ratio) {
  $percentage = floor($ratio * 100) . '%';
  $columns = drush_get_context('DRUSH_COLUMNS', 80);
  // Subtract 8 characters for the percentage, brackets, spaces and arrow.
  $progress_columns = $columns - 8;
  // If ratio is 1 (complete), the > becomes a = to make a full bar.
  $arrow = ($ratio < 1) ? '>' : '=';
  // Print a new line if ratio is 1 (complete). Otherwise, use a CR.
  $line_ending = ($ratio < 1) ? "\r" : "\n";

  // Determine the current length of the progress string.
  $current_length = (int) floor($ratio * $progress_columns);
  $progress_string = str_pad('', $current_length, '=');

  $output  = '[';
  $output .= $progress_string . $arrow;
  $output .= str_pad('', $progress_columns - $current_length);
  $output .= ']';
  $output .= str_pad('', 5 - strlen($percentage)) . $percentage;
  $output .= $line_ending;

  print $output;
}

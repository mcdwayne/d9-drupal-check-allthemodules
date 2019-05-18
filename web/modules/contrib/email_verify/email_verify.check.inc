<?php
/**
 * @file
 * User email check menu callback file for email_verify module.
 */

/**
 * Look though the users table for invalid emails.
 *
 * @param array $form
 *   The form definition.
 * @param array $form_state
 *   The current state of the form.
 *
 * @return array
 *   The form definition.
 */
function email_verify_user_check_form($form, &$form_state) {
  if (!empty($form_state['input'])) {
    drupal_set_message(t('Look for the verification results below the form.'));
  }

  $form['header'] = array(
    '#type' => 'fieldset',
    '#title' => t('User check'),
    '#collapsible' => TRUE,
    '#description' => t('
      On this page, you can list all existing users for whom their email address
      is not valid. Simply click the "Start" button to begin the process. If you
      make changes to the users, you will need to click the "Start" or "Update"
      buttons to see the new data.
    '),
  );
  $form['header']['form_help'] = array(
    '#markup' => t('
      <p>
      Because the checking process can take a very long time, especially for
      sites with thousands of users, to the point of this page being entirely
      useless, you may specify the number of users to check with the fields
      below.<br />
      <ul>
      <li>If "Number" is empty and "Offset" is empty, then all users in the
      database table will be verified.</li>
      <li>If "Number" has a value and "Offset" is empty, then the first "Number"
      of users in the database table will be verified.</li>
      <li>If "Number" is empty and "Offset" has a value, then all users after
      "Offset" to the end of the database table will be verified.</li>
      <li>If "Number" has a value and "Offset" has a value, then the "Number"
      of users after "Offset" will be verified.</li>
      </ul>
      </p>
    '),
  );
  $form['header']['number'] = array(
    '#type' => 'textfield',
    '#title' => t("Number"),
    '#size' => 15,
    '#description' => t('The number of users to verify.'),
  );
  $form['header']['offset'] = array(
    '#type' => 'textfield',
    '#title' => t("Offset"),
    '#size' => 15,
    '#description' => t('The number of users to skip before counting the number of users to verify.'),
  );
  $all_user_count = db_query("SELECT COUNT(DISTINCT uid) FROM users")->fetchField();
  $active_user_count = db_query("SELECT COUNT(DISTINCT uid) FROM users WHERE status = 1")->fetchField();
  $form['header']['disabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Include blocked/disabled users'),
    '#description' => t(
      'There are %all_user_count users registed with this site, of which, %active_user_count are active.',
      array(
        '%all_user_count' => $all_user_count,
        '%active_user_count' => $active_user_count,
      )
    ),
  );
  $form['header']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Start'),
  );

  // Get any records that have been stored to display.
  $rows = variable_get('email_verify_users_to_display', array());
  $user_rows = $rows;

  $headers = _email_verify_get_header();

  $form_count = format_plural(
    count($rows),
    'Found 1 user whose email address is not valid.',
    'Found @count users whose email addresses are not valid.'
  );

  if (!empty($rows)) {
    // Rename the button.
    $form['header']['submit']['#value'] = t('Update');

    // Set up the sorting.
    _email_verify_sort_rows($headers, $rows);

    // Set up the pager.
    $current_page = pager_default_initialize(count($rows), 25);
    // Break the total data set into page sized chunks.
    $pages = array_chunk($rows, 25, TRUE);

    // Add the users.
    if (!empty($pages)) {
      $user_rows = $pages[$current_page];
    }
  }

  // Theme and add the results.
  $form['table'] = array(
    '#markup' => theme(
      'table',
      array(
        'header' => $headers,
        'rows' => $user_rows,
        'caption' => $form_count,
        'empty' => t('All email address checked passed the verification(s).'),
      )
    ),
  );

  // Add the pager.
  $form['pager'] = array('#markup' => theme('pager'));

  return $form;
}

/**
 * Validate handler for the user check form.
 */
function email_verify_user_check_form_validate($form, &$form_state) {
  if (!empty($form_state['values']['number']) && !is_numeric(trim($form_state['values']['number']))) {
    form_set_error('number', t('The value in the "Number" field can only be an integer.'));
  }
  if (!empty($form_state['values']['offset']) && !is_numeric(trim($form_state['values']['offset']))) {
    form_set_error('offset', t('The value in the "Offset" field can only be an integer.'));
  }
}

/**
 * Submit handler for the user check form.
 */
function email_verify_user_check_form_submit($form, &$form_state) {
  // Clear any saved data.
  variable_del('email_verify_users_to_display');

  $disabled_users = FALSE;
  if (isset($form_state['values']['disabled']) && $form_state['values']['disabled'] == 1) {
    $disabled_users = TRUE;
  }

  // Prepare the operations.
  $user_count = 0;
  if (!empty($form_state['values']['number'])) {
    $user_count = trim($form_state['values']['number']);
  }

  $user_offset = 0;
  $current_uid = 0;
  if (!empty($form_state['values']['offset'])) {
    $user_offset = trim($form_state['values']['offset']);

    if (!empty($user_offset)) {
      // Get the user ID of the user to start the verification process with.
      $current_uid = _email_verify_batch_display_get_current_uid($user_offset, $disabled_users);
    }
  }

  // Get the number of users to verify.
  $max = _email_verify_batch_display_get_max($user_count, $user_offset, $disabled_users, $current_uid);

  $operations = array();
  $operations[] = array(
    '_email_verify_batch_display_process_batch',
    array($current_uid, $max, $disabled_users),
  );

  // Prepare the batch.
  $batch = array(
    'title' => t('Checking the email addresses of @user_count users. You may want to go get a cup of coffee now. Or maybe a sandwich.', array('@user_count' => $user_count)),
    'operations' => $operations,
    'progress_message' => t('Completed @current of @total operations.'),
    'error_message' => t('One or more errors were encountered processing the users.'),
    'finished' => '_email_verify_batch_finish_batch',
    'file' => drupal_get_path('module', 'email_verify') . '/email_verify.check.inc',
  );

  // Set the batch.
  batch_set($batch);
}

/**
 * Retrieves the user ID of the user to start the verification process with.
 *
 * @param int $user_offset
 *   The number of users to skip before beginning verification.
 * @param bool $disabled_users
 *   Indicates whether to include disabled users or not. A value of TRUE (or 1)
 *   includes them, where a value of FALSE (or 0) does not.
 *
 * @return int
 *   The user ID before the one to start the verification with. The verification
 *   process starts with the uid after this one, so that uid 0 is not verified.
 */
function _email_verify_batch_display_get_current_uid($user_offset, $disabled_users) {
  $query = 'SELECT uid FROM users';
  // Check for whether to include blocked users.
  if (empty($disabled_users)) {
    $query .= ' WHERE status = 1';
  }
  $query .= ' ORDER BY uid ASC LIMIT 1 OFFSET ' . $user_offset;
  return db_query($query)->fetchField();
}

/**
 * Retrieves the number of users to verify.
 *
 * @param int $user_count
 *   The number of user email addresses to verify.
 * @param int $user_offset
 *   The number of users to skip before beginning verification.
 * @param bool $disabled_users
 *   Indicates whether to include disabled users or not. A value of TRUE (or 1)
 *   includes them, where a value of FALSE (or 0) does not.
 * @param int $current_uid
 *   The user ID of the user to start the verification on.
 *
 * @return int
 *   The value that goes in the batch sandbox's 'max' variable, which is the
 *   maximum number of records to process.
 */
function _email_verify_batch_display_get_max($user_count, $user_offset, $disabled_users, $current_uid) {
  // Default to not running the batch.
  $max = 0;
  if (empty($user_count)) {
    // Set 'max' to the total number of users in the database.
    $query = 'SELECT COUNT(uid) FROM users';
    // Check for whether to include blocked users.
    if (empty($disabled_users)) {
      $query .= ' WHERE status = 1';
    }
    $max = db_query($query)->fetchField();

    // If the offset value is not empty, use it to modify $max.
    if (!empty($user_offset)) {
      $max = $max - $user_offset;
    }
  }
  else {
    if (empty($user_offset)) {
      // Set 'max' to number of users specified in the form.
      $max = $user_count;
    }
    else {
      // Set 'max' to number of users specified in the form, using the offset.
      $query = 'SELECT uid FROM users WHERE uid >= :start_uid';
      // Check for whether to include blocked users.
      if (empty($disabled_users)) {
        $query .= ' AND status = 1';
      }
      $query .= ' ORDER BY uid ASC LIMIT ' . $user_count;
      $results = db_query($query, array(':start_uid' => $current_uid))->fetchAll();
      $max = count($results);
    }
  }
  return $max;
}

/**
 * The batch process for checking the users.
 *
 * @param int $current_uid
 *   The user ID of the user to start the verification on.
 * @param int $max
 *   The maximum number of records to process.
 * @param bool $disabled_users
 *   Indicates whether to include disabled users or not. A value of TRUE (or 1)
 *   includes them, where a value of FALSE (or 0) does not.
 * @param array $context
 *   Used by the Batch API to keep track of and pass data from one operation to
 *   the next.
 */
function _email_verify_batch_display_process_batch($current_uid, $max, $disabled_users, array &$context) {
  // Used to store information to track progression between successive calls.
  if (empty($context['sandbox'])) {
    $context['sandbox'] = array();
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_uid'] = $current_uid;
    $context['sandbox']['max'] = $max;
  }

  $query_limit = 5;
  // Make sure no more records are processed than specified.
  if ($context['sandbox']['max'] - $context['sandbox']['progress'] < $query_limit) {
    $query_limit = $context['sandbox']['max'] - $context['sandbox']['progress'];
  }

  // Create and execute the query for selecting the user data.
  $query = 'SELECT uid, name, mail FROM {users} WHERE uid > :uid';
  // Check for whether to include blocked users.
  if (empty($disabled_users)) {
    $query .= ' AND status = 1';
  }
  $query .= ' ORDER BY uid ASC LIMIT ' . $query_limit;
  $users = db_query(
    $query,
    array(':uid' => $context['sandbox']['current_uid'])
  )->fetchAll();

  // If there are no more user email addresses to verify, set 'progress' to
  // 'max'.
  if (empty($users)) {
    $context['sandbox']['progress'] = $context['sandbox']['max'] = $max;
  }
  else {
    // Process the user list.
    foreach ($users as $user) {
      // Check the user account.
      $check_results = email_verify_check($user->mail);
      if (!empty($check_results['verification_message'])) {
        // Add the user to the list.
        $context['results']['users_to_display'][$user->uid] = array(
          'uid' => $user->uid,
          'name' => $user->name,
          'mail' => $user->mail,
          'reason' => $check_results['verification_message'],
          'link' => l(t('account'), 'user/' . $user->uid),
        );
      }
      if (!empty($check_results['debugging_text'])) {
        // Log and/or display the debugging information.
        email_verify_process_debug_information($check_results['debugging_text']);
      }

      // Update the progress information.
      $context['sandbox']['progress']++;
      $context['sandbox']['current_uid'] = $user->uid;
      $context['message'] = t('Processed email address @email (user ID @uid)', array('@email' => $user->mail, '@uid' => $user->uid));
    }
  }

  // Inform the batch engine the operations have not been completed, and provide
  // an estimation of the current completion level.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = ($context['sandbox']['progress'] >= $context['sandbox']['max']);
  }
}

/**
 * The function that is called when the batch is complete.
 */
function _email_verify_batch_finish_batch($success, $results, $operations) {
  if ($success) {
    if (!empty($results['users_to_display'])) {
      // Save the collected data for display.
      variable_set('email_verify_users_to_display', $results['users_to_display']);
    }
  }
  else {
    // An error occurred. $operations contains the operations that remained
    // unprocessed.
    $error_operation = reset($operations);
    drupal_set_message(
      t('An error occurred while processing @operation with arguments : @args',
        array(
          '@operation' => $error_operation[0],
          '@args' => print_r($error_operation[0], TRUE),
        )
      )
    );
  }
}

/**
 * Returns the header to use for the display table.
 *
 * @return array
 *   The header to use.
 */
function _email_verify_get_header() {
  return array(
    'uid' => array(
      'data' => t('User Id'),
      'field' => 'uid',
    ),
    'name' => array(
      'data' => t('Name'),
      'field' => 'name',
    ),
    'mail' => array(
      'data' => t('Email address'),
      'field' => 'mail',
    ),
    'reason' => array(
      'data' => t('Reason'),
    ),
    'account' => array(
      'data' => t(''),
    ),
  );
}

/**
 * Sorts the table rows.
 *
 * @param array $headers
 *   The headers of the table.
 * @param array $rows
 *   The data to sort.
 */
function _email_verify_sort_rows($headers, &$rows) {
  $order_field = tablesort_get_order($headers);
  $order = tablesort_get_sort($headers);
  if (!empty($order_field['sql'])) {
    $order_by = $order_field['sql'];

    switch ($order) {
      case 'desc':
        uasort($rows, function($a, $b) use($order_by) {
          if ($a[$order_by] == $b[$order_by]) {
            return 0;
          }
          return ($a[$order_by] > $b[$order_by]) ? -1 : 1;
        });
        break;

      case 'asc':
        uasort($rows, function($a, $b) use ($order_by) {
          if ($a == $b) {
            return 0;
          }
          return ($a[$order_by] < $b[$order_by]) ? -1 : 1;
        });
        break;
    }
  }
}

<?php

/**
 * @file
 *
 * CasperJS Drush integration.
 */

/**
 * Implements hook_drush_command().
 */
function casperjs_drush_command() {
  $items['casperjs'] = array(
    'description' => 'Wrapper for running CasperJS tests. Accepts extra options for casperjs command.',
    'callback' => 'drush_casperjs',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
    'arguments' => array(
      'tests' => 'A comma separated list of tests to run. If not provided, all tests at the tests directory will be run.',
    ),
    'options' => array(
      'test-root' => 'Overrides the default location of tests. Path is relative to Drupal\'s root.',
      'url' => 'The base URL to use for test requests. Defaults to the URL set by Drush or the current Drush alias.',
      'includes' => 'Comma-separated list of files to include before each test file execution. Paths are relative to Drupal\'s root.',
      'cookies-file' => 'Sets the file name to store the persistent cookies. If not provided a random file in the system temporary directory will be used.',
      'anonymous' => 'Runs the tests without user sessions. Useful to speed up individual test runs.',
      'select' => 'Select the test to run interactively from a list of tests in the test-root directory.',
      '{casperjs-option-name}' => 'Any option supported by the native casperjs command. For example: --xunit=test-results.xml',
    ),
    'examples' => array(
      'drush casperjs --url=http://drupal.local' => 'Runs all tests located at the tests directory against http://drupal.local',
      'drush casperjs --url=http://drupal.local --test-root=../testing/tests' => 'Runs all tests located at the testing/tests directory against http://drupal.local',
      'drush casperjs --url=http://drupal.local --test-root=../testing/tests --select' => 'Lists all tests located at the testing/tests directory and allows choosing which test(s) to run.',
      'drush casperjs --url=http://drupal.local homepage.js' => 'Runs homepage.js test against http://drupal.local.',
      'drush casperjs --url=http://drupal.local --anonymous homepage.js' => 'Runs homepage.js test against http://drupal.local without user sessions.',
      'export CASPERJS_TEST_CAPTURE=true && drush -v casperjs --url=http://drupal.local --log-level=debug' => 'Runs all tests against http://drupal.local with extra verbose logging and taking screenshots on failed assertions.',
      'drush casperjs --url=http://drupal.local --includes=../testing/users.js,../testing/common.js' => 'Runs all tests against http://drupal.local and loads the extra files users.js and common.js. Useful to override casper object settings such as the list of users to log ing.',
      'drush casperjs --url=http://drupal.local --xunit=test-results.xml --engine=slimerjs' => 'Runs all the tests against http://drupal.local using the slimerjs engine and exports the result to a xUnit file named test-results.xml.',
    ),
    'allow-additional-options' => TRUE,
  );

  return $items;
}

/**
 * Implements drush_COMMANDFILE().
 */
function drush_casperjs($tests = NULL) {
  $drupal_root = drush_get_context('DRUSH_SELECTED_DRUPAL_ROOT');
  $command = 'casperjs test --verbose --root=' . $drupal_root;

  // Set the root where tests are. Defaults to the tests directory within this
  // project.
  if (!$root = drush_get_option('test-root')) {
    $root =  dirname(__FILE__) . '/tests';
  }

  // If the --select option is set we generate a list of files in the $root
  // directory that end with .js. These are all test files. Then we list them
  // for the user to choose from.
  $select = drush_get_option('select', FALSE);
  if ($select) {
    // Find all files ending in .js.
    $test_files = drush_scan_directory($root, '/.*?\.js$/s');
    $options['All'] = dt('All');
    foreach ($test_files as $file) {
      $relative_path = str_replace($root . '/', '', $file->filename);
      $options[$relative_path] = $relative_path;
    }

    // Present a list to the user and let them choose an option.
    $choice = drush_choice($options, dt('Choose a test to run:'));
    if (!$choice) {
      return drush_user_abort();
    }

    // If the user selected all, just set $tests to NULL and all the tests in
    // the test-root directory will be run. Otherwise, set $tests to the one
    // that the user choose and we'll run that one.
    $tests = $choice === 'All' ? NULL : $choice;
  }

  if (!drush_get_option('cookies-file')) {
    $cookie_file = drush_tempnam('casper-cookie-');
    $command .= ' --cookies-file=' . drush_escapeshellarg($cookie_file);
  }

  // Add Drupal's URL for CasperJS to use in requests.
  $url = drush_get_option('url');
  if (empty($url)) {
    // URL was not provided, attempt to extract it from Drush's context.
    $url = drush_get_context('DRUSH_SELECTED_URI');
  }
  $command .= ' --url=' . drush_escapeshellarg($url);

  // Add default include files.
  $includes = array(dirname(__FILE__) . '/includes/common.js', dirname(__FILE__) . '/includes/session.js');

  // Add custom include files. Useful to override and extend default
  // behaviors. File paths are relative to Drupal's root.
  if ($custom_includes = drush_get_option('includes')) {
    $custom_includes = explode(',', $custom_includes);
    foreach ($custom_includes as $include_file) {
      $includes[] = $drupal_root . '/' . $include_file;
    }
  }
  $command .= ' --includes=' . drush_escapeshellarg(implode(',', $includes));

  // Add pre and post scripts if the tests are to be run with sessions.
  $anonymous = drush_get_option('anonymous');
  if (!$anonymous) {
    $command .= ' --pre=' . drush_escapeshellarg(dirname(__FILE__) . '/includes/pre-test.js');
    $command .= ' --post=' . drush_escapeshellarg(dirname(__FILE__) . '/includes/post-test.js');
  }

  // If a list of tests to run were passed, append them to the command.
  // Otherwise, just set the root where tests are located so CasperJS
  // will run all of them.
  $tests_to_run = $root;
  if ($tests) {
    $tests_to_run = '';
    foreach (explode(',', $tests) as $test_file) {
      $tests_to_run .= ' ' . drush_escapeshellarg($root . '/' . $test_file);
    }
  }
  $command .= ' ' . $tests_to_run;

  // Append additional CasperJS options to the command.
  $args = array();
  foreach (drush_get_original_cli_args_and_options() as $arg) {
    // Don't pass some options through.
    if (strpos($arg, '--simulate') !== FALSE
      || strpos($arg, '--includes') !== FALSE
      || strpos($arg, '-') !== 0) {
      continue;
    }
    $args[] = $arg;
  }
  if (!empty($args)) {
    $command .= ' ' . implode(' ', $args);
  }

  echo $command . "\n";
  $result = drush_shell_proc_open($command);
  if ($result !== 0) {
    return drush_set_error('CASPERJS_TEST_FAILED', dt('Tests failed.'));
  }
  else {
    drush_log(dt('Tests succeeded.'), 'success');
    return TRUE;
  }
}

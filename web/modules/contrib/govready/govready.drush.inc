<?php

/**
 * @file
 * Drush commands for GovReady module.
 */

/**
 * Implements hook_drush_command().
 */
function govready_drush_command() {

  $items['govready-collect'] = array(
    'description' => 'Push data to the GovReady API.',
    'aliases' => array('govready'),
    'arguments' => array(
      'type' => 'The type of data to send to the GovReady API. Options: all, stack, accounts, modules, domain',
    ),
    'examples' => array(
      'drush govready-collect all' => 'Collect and send data from stack, accounts, modules.',
      'drush govready-collect accounts' => 'Only collect and send account data.',
    ),
  );

  $items['govready-scan'] = array(
    'description' => 'Push scan results to the GovReady API.',
    'arguments' => array(
      'name' => 'The machine name of the scan that is running',
    ),
    'examples' => array(
      'drush govready-scan myscan < scan-output.json' => 'Send scan information from a json file.',
      'run-scan.sh | drush govready-scan myscan' => 'Run a scan and pipe the result to the GovReady API.',
    ),
  );

  $items['govready-mode'] = array(
    'description' => 'Set the GovReady API mode.',
    'arguments' => array(
      'mode' => 'The mode to set the GovReady API as. Options: automatic, manual, local',
    ),
    'examples' => array(
      'drush govready-mode manual' => 'Set the manual mode on the GovReady API.',
    ),
  );

  $items['govready-initialize'] = array(
    'description' => 'Initialize the GovReady module\'s communication with the GovReady API.',
    'aliases' => array('govready-init'),
  );

  $items['govready-site'] = array(
    'description' => 'Get the stored Site information from GovReady.',
  );

  $items['govready-reset'] = array(
    'description' => 'Reset your GovReady site.',
  );

  $items['govready-demo-load'] = array(
    'description' => 'Existing Measures, Submissions, and Contacts will be deleted and replaced with demo data.',
  );


  return $items;
}

/**
 * Callback for the govready-collect command
 */
function drush_govready_collect($type = FALSE) {

  if (!$type) {
    $type = drush_prompt(dt('Data type to collect. Options: all, stack, accounts, modules, domain. Default: all:'));
    $type = !empty($type) ? $type : 'all';
  }

  // Check for correct argument
  $correct_args = array('all', 'stack', 'accounts', 'modules', 'domain');
  if (!in_array($type, $correct_args)) {
    return drush_set_error(dt('"@type" is not a valid statement type. Please choose between all, stack, accounts, modules, domain.', array('@type' => $type)));
  }

  // Check that GovReady has been initialized
  $options = \Drupal::config('govready.settings')->get('govready_options');
  if (empty($options['siteId'])) {
    return drush_set_error(dt('The GovReady module has not been initialized. Run "drush govready-init".'));
  }

  module_load_include('class.php', 'govready', 'lib/govready-agent');
  $agent = new \Drupal\govready\Controller\GovreadyAgent();

  // Split "all" into all of the values (getting rid of all and domains)
  array_shift($correct_args);
  array_pop($correct_args);
  $endpoints = $type == 'all' ? $correct_args : array($type);

  foreach ($endpoints as $item) {

    // Do a little rewriting of Drupal-friendly labels to the GovReady API endpoints
    $prefix = 'sites';
    $item = ($item == 'modules') ? 'plugins' : $item;
    

    // Call the appropriate function to collect data in govready-agent.class.php
    if ($item == 'domain') {
      $endpoint = '/monitor/' . $options['siteId'] . '/domain';
    }
    else {
      $data = call_user_func(array($agent, $item));
      $endpoint = '/users/' . $options['siteId'] . '/' . $item;
    }
    $return = govready_api($endpoint, 'POST', $data);

    // @todo: debug flag?
    //print_r($return);

    drush_print(dt('Successfully called the GovReady API !type endpoint: !endpoint', array('!type' => $item, '!endpoint' => $endpoint)));
  } // foreach
  
}


/**
 * Callback for the govready-scan command
 */
function drush_govready_scan($name = FALSE) {

  // Check that GovReady has been initialized
  $options = \Drupal::config('govready.settings')->get('govready_options');
  if (empty($options['siteId'])) {
    return drush_set_error(dt('The GovReady module has not been initialized. Run "drush govready-init".'));
  }

  $data = '';
  while (false !== ($line = fgets(STDIN))) {
    $data .= $line;
  }

  $args = array(
    'name' => $name,
    'data' => $data
  );
  //print_r($args);

  $endpoint = '/sites/' . $options['siteId'] . '/scan';
  $return = govready_api($endpoint, 'POST', $args);

  print_r($return);
}



/**
 * Callback for the govready-mode command
 */
function drush_govready_mode($mode = FALSE) {

  // Check for correct argument
  $correct_args = array('automatic', 'manual', 'local');
  if (!in_array($mode, $correct_args)) {
    return drush_set_error(dt('"@mode" is not a valid mode. Please choose between automatic, manual, local.', array('@mode' => $mode)));
  }

  // Check that GovReady has been initialized
  $options = \Drupal::config('govready.settings')->get('govready_options');
  if (empty($options['siteId'])) {
    return drush_set_error(dt('The GovReady module has not been initialized. Run "drush govready-init".'));
  }

  $endpoint = '/sites/' . $options['siteId'] . '/changeMode';
  // @todo: better message?
  drush_print(govready_api($endpoint, 'POST', array('mode' => $mode)));

}

/**
 * Callback for the govready-mode command
 */
function drush_govready_initialize($mode = FALSE) {

  // Check that GovReady has been initialized
  $options = \Drupal::config('govready.settings')->get('govready_options');
  if (!empty($options['siteId'])) {
    return drush_set_error(dt('The GovReady module has already been initialized.'));
  }

  drush_start_browser('admin/reports/govready', $sleep = FALSE);

   drush_print(dt('We are opening your browser to /admin/reports/govready.  Automatic initialization of the GovReady API Site via Drush is coming soon.'));
}

/**
 * Callback for the govready-mode command
 */
function drush_govready_site() {

  // Check that GovReady has been initialized
  $options = \Drupal::config('govready.settings')->get('govready_options');
  if (empty($options['siteId'])) {
    return drush_set_error(dt('The GovReady module has not been initialized. Run "drush govready-init".'));
  }

  $endpoint = '/sites/' . $options['siteId'];
  drush_print(dt('Getting site from !endpoint:', array('!endpoint' => $endpoint)));
  print_r(govready_api($endpoint, 'GET'));

}


/**
 * Callback for the govready-mode command
 */
function drush_govready_reset() {

  // Check that GovReady has been initialized
  $options = \Drupal::config('govready.settings')->get('govready_options');
  if (empty($options['siteId'])) {
    return drush_set_error(dt('The GovReady module has not been initialized. Run "drush govready-init".'));
  }

  if (drush_confirm('Are you sure you want to delete your site? This will delete all data that you have pushed to the GovReady API, including contacts, submissions, and scans.')) {
    $endpoint = '/sites/' . $options['siteId'];
    //print_r(govready_api($endpoint, 'DELETE'));

    \Drupal::configFactory()->getEditable('govready.settings')
      ->set('govready_options', NULL)
      ->set('govready_token', NULL)
      ->save();

  }
  else {
    drush_user_abort();
  }

}


/**
 * Callback for the govready-mode command
 */
function drush_govready_demo_load() {

  // Check that GovReady has been initialized
  $options = \Drupal::config('govready.settings')->get('govready_options');
  if (empty($options['siteId'])) {
    return drush_set_error(dt('The GovReady module has not been initialized. Run "drush govready-init".'));
  }

  if (drush_confirm('Are you sure you want to import demo data?  Existing Measures, Submissions, and Contacts will be deleted.')) {
    
    // Update Measures, Submissions, and Contacts
    $endpoint = '/sites/' . $options['siteId'] .'/load/demo';
    print_r(govready_api($endpoint, 'POST'));

    // Update agent data
    drush_govready_collect('all');
    drush_govready_collect('domain');
  }
  else {
    drush_user_abort();
  }

}



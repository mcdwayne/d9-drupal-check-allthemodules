<?php

/**
 * @file
 * This file contains functions using the Redmine REST API.
 */

/**
 * Retrieves the Redmine user information for the currently configured
 * API key and returns the Redmine UID.
 *
 * @return
 * Redmine user ID based on API key.
 */
function feedmine_rmuid(){
  // Retrieve Redmine API settings
  $rm_api_key  = \Drupal::config('feedmine.settings')->get('feedmine_rmapikey');
  $rm_base_url = \Drupal::config('feedmine.settings')->get('feedmine_rmurl');

  // Verify settings
  if(!isset($rm_api_key)){
    $msg = t('Unable to retrieve Redmine project list because no API key is present.');
    \Drupal::logger('feedmine')->notice($msg);
    return;
  }

  // Prepare request
  $options = array(
    'headers' => array(
      'Content-Type' => 'application/json',
      'X-Redmine-API-Key' => $rm_api_key,
    ),
  );
  $rm_endpoint = '/users/current.json';
  $request_url = $rm_base_url . $rm_endpoint;

  // Get response
  //$response = drupal_http_request($request_url, $options);

  // Verify response and retrieve results
  try {
    $response = \Drupal::httpClient()->get($request_url, $options);
    $data = (string) $response->getBody();
    
    if(!empty($data)) {
        $data = json_decode($data);
        return $data->user->id;
    }
    else {
        $msg = t('Unable to retreve user ID from Redmine.<br><strong>Response:</strong><br><code>!response</code>');
        $vars = array('!response' => json_encode($response));
        //watchdog('feedmine', $msg, $vars);
        \Drupal::logger('feedmine')->error($msg, $vars);
        return FALSE;
    }

  }
  catch (RequestException $e) {
    \Drupal::logger('feedmine')->error($e);
    return FALSE;
  }

}

/**
 * Retrieve a list of projects from the specified Redmine instance.
 *
 * @return
 * Returns a key / value pair array for projects currently available
 * within Redmine with project id as key and name as value.
 */
function feedmine_getrmprojects(){
  // Retrieve Redmine API settings
  $rm_api_key  = \Drupal::config('feedmine.settings')->get('feedmine_rmapikey');
  $rm_base_url = \Drupal::config('feedmine.settings')->get('feedmine_rmurl');

  // Verify settings
  if(!isset($rm_api_key)){
    $msg = t('Unable to retrieve Redmine project list because no API key is present.');
    \Drupal::logger('feedmine')->notice($msg);;
    return;
  }

  // Prepare request
  $options = array(
    'headers' => array(
      'Content-Type' => 'application/json',
      'X-Redmine-API-Key' => $rm_api_key,
    ),
  );
  $rm_endpoint = '/projects.json';
  $rm_options = array(
    'limit' => '100',
  );
  $request_url = $rm_base_url . $rm_endpoint . '?' . drupal_http_build_query($rm_options);

  // Get response
  try {
    $response = \Drupal::httpClient()->get($request_url, $options);
    $data = (string) $response->getBody();
    
    if(!empty($data)) {
        $results = json_decode($data);
      if(count($results->projects) < 1){
        return;
      }
    }
    else {
      $msg = t('Recieved a bad response while attempting to retrieve Redmine projects.<br><strong>Response as JSON:</strong><br><code>!response</code>');
      $vars = array('!response' => json_encode($response));
      \Drupal::logger('feedmine')->error($msg, $vars);
      return;
    }

  }
  catch (RequestException $e) {
    \Drupal::logger('feedmine')->error($e);
    return FALSE;
  }

  // Parse results and return list of project names keyed by project id.
  foreach($results->projects as $key => $project){
    $rm_projects[$project->id] = $project->name;
  };
  return $rm_projects;
}

/**
 * Upload file contents to Redmine and retrieve a token used to attach
 * the file to a Redmine issue later when we create the issue.
 *
 * @param1
 * Contents of the file.
 * @return
 * A token used to attach the file to a Redmine issues.
 */
function feedmine_issue_attach($file_contents){
//    print_r($file_contents); die();
  // Retrieve Redmine API settings
  $rm_base_url = \Drupal::config('feedmine.settings')->get('feedmine_rmurl');
  $rm_api_key  = \Drupal::config('feedmine.settings')->get('feedmine_rmapikey');
  $rm_proj_id  = \Drupal::config('feedmine.settings')->get('feedmine_rmprojectid');

  // Prepare request.
  $options = array(
    'headers' => array(
      'Content-Type' => 'application/octet-stream',
      'X-Redmine-API-Key' => $rm_api_key,
    ),
    'body' => json_encode($file_contents),
  );
  $rm_endpoint = '/uploads.json';
  $request_url = $rm_base_url . $rm_endpoint;

  // Submit request and get response.
  try {
    $response = \Drupal::httpClient()->post($request_url, $options);
    $data = (string) $response->getBody();
    if(!empty($data)) {
      $results = json_decode($data);
    }
  }
  catch (RequestException $e) {
    \Drupal::logger('feedmine')->error($e);
    return FALSE;
  }

  // Verify and return results.
  if(isset($results->upload->token)){
    $token = $results->upload->token;
    return $token;
  }
  else {
    $msg = t("Unable to locate file upload token in response data.<br><br><strong>JSON Response results:</strong><br><code>!results</code>");
    $vars = array('!results' => json_encode($data));
    \Drupal::logger('feedmine')->error($msg, $vars);
    return;
  }
}

function drupal_http_build_query(array $query, $parent = '') {
  $params = array();

  foreach ($query as $key => $value) {
    $key = ($parent ? $parent . '[' . rawurlencode($key) . ']' : rawurlencode($key));

    // Recurse into children.
    if (is_array($value)) {
      $params [] = drupal_http_build_query($value, $key);
    }
    // If a query parameter value is NULL, only append its key.
    elseif (!isset($value)) {
      $params [] = $key;
    }
    else {
      // For better readability of paths in query strings, we decode slashes.
      $params [] = $key . '=' . str_replace('%2F', '/', rawurlencode($value));
    }
  }

  return implode('&', $params);
}
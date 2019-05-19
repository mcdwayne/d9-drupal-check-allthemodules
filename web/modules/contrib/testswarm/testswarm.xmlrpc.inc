<?php
/**
 * XMLRPC callback to save tests after they have run.
 * @see testswarm_test_done().
 */
function testswarm_test_save_xmlrpc($testswarm_test, $tests, $logs, $time, $hash) {
  // @TODO: use user_hash_password?
  if (testswarm_xmlrpc_check_hash($time, $hash)) {
    testswarm_test_save($testswarm_test, $tests, $logs);
    return TRUE;
  }
  else {
    return xmlrpc_error(401, t('Access denied: Wrong hash.'));
  }

}

/**
 * XMLRPC callback to delete tests based on the caller.
 */
function testswarm_test_delete_xmlrpc($caller, $time, $hash) {
  if (testswarm_xmlrpc_check_hash($time, $hash)) {
    testswarm_test_delete($caller);
    return TRUE;
  }
  else {
    return xmlrpc_error(401, t('Access denied: Wrong hash.'));
  }
}

/**
 * Validate a hash for a XMLRPC callback.
 */
function testswarm_xmlrpc_check_hash($time, $hash) {
  return testswarm_xmlrpc_get_hash($time) == $hash;
}

/**
 * Get a hash for an XMLRPC call.
 */
function testswarm_xmlrpc_get_hash($time = 0) {
  $time = $time == 0 ? REQUEST_TIME : $time;
  //@todo: Use user_password_hash?
  return md5($time . testswarm_shared_secret());
}

/**
 * Get or set the shared secret for XMLRPC calls.
 */
function testswarm_shared_secret($secret = '') {
  $config = config('testswarm.settings');
  if (empty($secret)) {
    return $config->get('testswarm_shared_secret');
  }
  $config->set('testswarm_shared_secret', $secret);
  $config->save();
}


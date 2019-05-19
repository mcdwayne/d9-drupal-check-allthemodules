<?php

/**
 * @file
 */

/**
 * Payment hooks.
 */
function hook_worldcore($action, $params) {

  switch ($action) {
    case 'load':
      return worldcore_pid_load($params['pid']);

    break;
    case 'insert':
      return _worldcore_createpayment($params);

    break;
    case 'delete':
      return _worldcore_deletepayment($params['pid']);

    break;
    case 'enroll':
      return _worldcore_enrollpayment($params['pid'], $params['account'], $params['time']);

    break;
    default:
      return FALSE;
    break;
  }

}

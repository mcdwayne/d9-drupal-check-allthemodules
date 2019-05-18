<?php

/**
 * @file
 * API documentation for ACL.
 */

/**
 * Explain what your ACL grant records mean.
 */
function hook_acl_explain($acl_id, $name, $figure, $users = NULL) {
  if (empty($users)) {
    return "ACL (id=$acl_id) would grant access to $name/$figure.";
  }

  return "ACL (id=$acl_id) grants access to $name/$figure to the listed user(s).";
}


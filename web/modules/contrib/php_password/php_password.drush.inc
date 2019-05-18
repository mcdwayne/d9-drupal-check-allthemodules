<?php

/**
 * @file
 * Command line drush helpers.
 */

/**
 * Implements hook_drush_command().
 */
function php_password_drush_command() {
  $items['password-hash-cost'] = [
    'description' => 'Calculate an ideal hash cost for your hardware.',
    'arguments' => [
      'run_time' => 'Desired millisecond cost. Ideal values are between 100 and 500.',
    ],
    'drupal dependencies' => ['php_password'],
    'required-arguments' => 1,
  ];

  return $items;
}

/**
 * Super trivial benchmark to find a cost based on desired runtime.
 *
 * @param string $run_time
 *   Millisecond hash runtime.
 *
 * @return array
 */
function drush_php_password_password_hash_cost($run_time) {
  $password = 'I8ASD1\-?Z7cl4EB';
  $cost = 3;

  do {
    ++$cost;
    $start = microtime(TRUE);
    password_hash($password, PASSWORD_DEFAULT, ['cost' => $cost]);
    $time = microtime(TRUE) - $start;
  } while ($time < ($run_time / 1000));

  drush_print('Desired cost: ' . $cost);
}

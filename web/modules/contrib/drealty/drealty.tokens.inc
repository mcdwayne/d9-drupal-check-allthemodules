<?php

/**
 * @file
 * Token functionality for dRealty.
 */

/**
 * Implements hook_token_info().
 */
function drealty_token_info() {
  return array(
    'types' => array(
      'drealty-class' => array(
        'name' => t('Drealty class'),
        'description' => t('Tokens related to a Drealty class.'),
        'needs-data' => 'drealty-class',
      ),
    ),
    'tokens' => array(
      'drealty-class' => array(
        'systemname' => array(
          'name' => t('System name'),
          'description' => t('The System Name of the class.'),
        ),
        'standardname' => array(
          'name' => t('Standard name'),
          'description' => t('The Standard Name of the class.'),
        ),
        'visiblename' => array(
          'name' => t('Visible name'),
          'description' => t('The Visible Name of the class.'),
        ),
        'last_updated' => array(
          'name' => t('Last updated'),
          'description' => t('Timestamp for the last successful update. (%date)', array('%date' => format_date(REQUEST_TIME, 'custom', 'Y-m-d\TH:i:s', NULL, NULL))),
          'type' => 'date',
        ),
      ),
    ),
  );
}

/**
 * Implements hook_tokens().
 */
function drealty_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $replacements = array();

  $sanitize = !empty($options['sanitize']);

  if ($type == 'drealty-class' && !empty($data['drealty-class'])) {
    $class = $data['drealty-class'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'systemname':
          $replacements[$original] = $sanitize ? check_plain($class->systemname) : $class->systemname;
          break;
        case 'standardname':
          $replacements[$original] = $sanitize ? check_plain($class->standardname) : $class->standardname;
          break;
        case 'visiblename':
          $replacements[$original] = $sanitize ? check_plain($class->visiblename) : $class->visiblename;
          break;
        case 'last_updated':
          $replacements[$original] = format_date($class->lastupdate, 'custom', 'Y-m-d\TH:i:s', NULL, NULL);
      }
    }

    if ($timestamp_tokens = token_find_with_prefix($tokens, 'last_updated')) {
      $replacements = token_generate('date', $timestamp_tokens, array('date' => $class->lastupdate), $options);
    }
  }

  return $replacements;
}
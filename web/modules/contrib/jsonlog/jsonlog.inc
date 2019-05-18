<?php
/**
 * @file
 * JSONlog module helper functions.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\jsonlog\Logger\JsonLogData;

/**
 * Adds this module's setting fields to the system logging settings form.
 *
 * @see jsonlog_form_system_logging_settings_alter()
 *
 * @param array &$form
 * @param FormStateInterface &$form_state
 */
function _jsonlog_form_system_logging_settings_alter(&$form, FormStateInterface $form_state) {
  $config = \Drupal::configFactory()->getEditable('jsonlog.settings');
  $form['#attributes']['autocomplete'] = 'off';

  // Defaults.
  $siteid_default = jsonlog_default_site_id();
  if (!($dir_default = jsonlog_default_dir())) {
    \Drupal::messenger()->addMessage(t('Failed to establish the server\'s default logging directory.', [], ['context' => 'module:jsonlog']), 'error');
  }

  // These translations are used a lot.
  $t_overridden = t('overridden', [], ['context' => 'module:jsonlog']);

  // Using 'emergency' as threshold is simply not an option; because it's falsy
  // (expensive type checks, FALSE), and wouldn't make sense anyway.
  $severity_levels = RfcLogLevel::getLevels();
  unset($severity_levels[0]);

  $form['jsonlog'] = [
    '#type' => 'details',
    '#title' => 'JSON Log',
    '#open' => TRUE,
  ];
  $form['jsonlog']['description'] = [
    '#type' => 'markup',
    '#markup' => '<p>'
      . t('Any server variable \'drupal_[jsonlog_variable_name]\' will override \'[jsonlog_variable_name]\' drupal conf. variable, except for tags (will be combined).', [], ['context' => 'module:jsonlog'])
      . '<br/>' . t('Tip: Server environment variables set in virtual host or .htaccess won\'t be visible by drush/CLI; /etc/environment might be your friend instead.', [], ['context' => 'module:jsonlog'])
      . '</p>',
  ];

  // Severity threshold.
  if (($severity_threshold = getenv('drupal_jsonlog_severity_threshold'))) {
    $overridden = TRUE;
  }
  else {
    $severity_threshold = $config->get('jsonlog_severity_threshold');
    $overridden = FALSE;
  }
  $form['jsonlog']['jsonlog_severity_threshold'] = [
    '#type' => 'select',
    '#title' => t(
      'Don\'t log events that are less severe than [jsonlog_severity_threshold] @overridden',
      ['@overridden' => $overridden ? $t_overridden : ''],
      ['context' => 'module:jsonlog']
    ),
    '#description' => t('Emergency is not an option.', [], ['context' => 'module:jsonlog']),
    '#options' => $severity_levels,
    '#default_value' => $severity_threshold,
  ];
  if ($overridden) {
    $form['jsonlog']['jsonlog_severity_threshold']['#attributes'] = [
      'disabled' => 'disabled',
    ];
  }

  // Truncation.
  if (($truncate = getenv('drupal_jsonlog_truncate')) !== FALSE) {
    $overridden = TRUE;
  }
  else {
    $truncate = $config->get('jsonlog_truncate');
    $overridden = FALSE;
  }
  $form['jsonlog']['jsonlog_truncate'] = [
    '#type' => 'textfield',
    '#title' => t(
      'Truncate events to [jsonlog_truncate] @overridden',
      ['@overridden' => $overridden ? $t_overridden : ''],
      ['context' => 'module:jsonlog']
    ),
    '#description' => t(
      'Zero means no truncation. Log entries longer than the file system\'s block size (typically 4 Kb) should not result in garbled logs (due to concurrent file writes), because the logger uses write locking. Defaults to @default (Kb).',
      [
        '@default' => $truncate,
      ],
      ['context' => 'module:jsonlog']
    ),
    '#default_value' => $truncate,
    '#size' => 5,
    '#field_suffix' => t('Kb', [], ['context' => 'module:jsonlog']),
  ];
  if ($overridden) {
    $form['jsonlog']['jsonlog_truncate']['#attributes'] = [
      'disabled' => 'disabled',
    ];
  }

  // Site ID.
  if (($site_id = getenv('drupal_jsonlog_siteid'))) {
    $overridden = TRUE;
  }
  else {
    if (!($site_id = $config->get('jsonlog_siteid'))) {
      $site_id = $siteid_default;
    }
    $overridden = FALSE;
  }
  $form['jsonlog']['jsonlog_siteid'] = [
    '#type' => 'textfield',
    '#title' => t(
      'Site ID [jsonlog_siteid] @overridden',
      ['@overridden' => $overridden ? $t_overridden : ''],
      ['context' => 'module:jsonlog']
    ),
    '#description' => t(
      'Spaces and quotes get replaced by hyphens. Defaults to the server\'s hostname and the site\'s database name and prefix (if any): @default',
      [
        '@default' => $siteid_default,
      ],
      ['context' => 'module:jsonlog']
    ),
    '#required' => TRUE,
    '#default_value' => $site_id,
  ];
  if ($overridden) {
    $form['jsonlog']['jsonlog_siteid']['#attributes'] = [
      'disabled' => 'disabled',
    ];
  }

  // Canonical site identifier across site instances.
  if (($canonical = getenv('drupal_jsonlog_canonical'))) {
    $overridden = TRUE;
  }
  else {
    $canonical = $config->get('jsonlog_canonical');
    $overridden = FALSE;
  }
  $form['jsonlog']['jsonlog_canonical'] = [
    '#type' => 'textfield',
    '#title' => t(
      'Canonical site name [jsonlog_canonical] @overridden',
      ['@overridden' => $overridden ? $t_overridden : ''],
      ['context' => 'module:jsonlog']
    ),
    '#description' => t(
      'For simpler identification across load balanced site instances.',
      [],
      ['context' => 'module:jsonlog']
    ),
    '#required' => FALSE,
    '#default_value' => $canonical,
  ];
  if ($overridden) {
    $form['jsonlog']['jsonlog_canonical']['#attributes'] = [
      'disabled' => 'disabled',
    ];
  }

  // Timestamped file; default 'Ymd',
  // values: 'Ymd' (~ YYYYMMDD)|'YW' (~ YYYYWW)|'Ym' (~ YYYYMM)|'none'.
  if (($file_time = getenv('drupal_jsonlog_file_time'))) {
    $overridden = TRUE;
  }
  else {
    if (!($file_time = $config->get('jsonlog_file_time'))) {
      $file_time = 'Ymd';
    }
    $overridden = FALSE;
  }
  $form['jsonlog']['jsonlog_file_time'] = [
    '#type' => 'select',
    '#title' => t(
      'Timestamped log file name [jsonlog_file_time] @overridden',
      ['@overridden' => $overridden ? $t_overridden : ''],
      ['context' => 'module:jsonlog']
    ),
    '#description' => t(
      'Default: Day (\'Ymd\' ~ YYYYMMDD)',
      [],
      ['context' => 'module:jsonlog']
    ),
    '#options' => [
      // Deliberaterately not '_none'.
      'none' => t('None - use the same file forever', [], ['context' => 'module:jsonlog']),
      'Ymd' => t('Day (\'Ymd\' ~ YYYYMMDD)', [], ['context' => 'module:jsonlog']),
      'YW' => t('Week (\'YW\' ~ YYYYWW)', [], ['context' => 'module:jsonlog']),
      'Ym' => t('Month (\'Ym\' ~ YYYYMM)', [], ['context' => 'module:jsonlog']),
    ],
    '#default_value' => $file_time,
  ];
  if ($overridden) {
    $form['jsonlog']['jsonlog_file_time']['#attributes'] = [
      'disabled' => 'disabled',
    ];
  }

  // Dir.
  if (($dir = getenv('drupal_jsonlog_dir'))) {
    $overridden = TRUE;
  }
  else {
    if (!($dir = $config->get('jsonlog_dir'))) {
      $dir = $dir_default;
    }
    $overridden = FALSE;
  }
  $form['jsonlog']['jsonlog_dir'] = [
    '#type' => 'textfield',
    '#title' => t(
      'Log directory [jsonlog_dir] @overridden',
      ['@overridden' => $overridden ? $t_overridden : ''],
      ['context' => 'module:jsonlog']
    ),
    '#description' => t(
      'Defaults to PHP ini \'error_log\' path + /drupal-jsonlog:@default! NB: The web server user (www-data|apache) probably isn\'t allowed to write to a file in the \'error_log\' path - create a sub dir (like \'drupal-jsonlog\') as root user, and do a chown or chmod on that sub dir to make it writable (and executable) by the web server user.',
      [
        '@default' => '&nbsp; ' . $dir_default,
      ],
      ['context' => 'module:jsonlog']
    ),
    '#required' => FALSE,
    '#default_value' => $dir,
    '#size' => 80,
    '#field_suffix' => '/' . $site_id . ($file_time == 'none' ? '' : ('.' . date($file_time))) . '.json.log',
  ];
  if ($overridden) {
    $form['jsonlog']['jsonlog_dir']['#attributes'] = [
      'disabled' => 'disabled',
    ];
  }

  // Tags.
  $tags_server = ($tags = getenv('drupal_jsonlog_tags')) !== FALSE ? $tags : '';
  $form['jsonlog']['jsonlog_tags'] = [
    '#type' => 'textfield',
    '#title' => t('Tags [jsonlog_tags]', [], ['context' => 'module:jsonlog']),
    '#description' => t(
      'Comma-separated list of tags. Tags set by server environment variable will be combined with tags set here.',
      [],
      ['context' => 'module:jsonlog']
    ),
    '#default_value' => $tags_site = $config->get('jsonlog_tags'),
    '#size' => 100,
    '#field_prefix' => $tags_server !== '' ? ($tags_server . ', ') : '',
  ];

  $form['jsonlog']['examples'] = [
    '#type' => 'details',
    '#title' => t('JSONlog entry example', [], ['context' => 'module:jsonlog']),
    '#open' => FALSE,
  ];

  // Create a dummy entry as an example.
  $entry = new JsonLogData($site_id, $canonical);
  $entry->setTags($tags_server, $tags_site);
  $entry->setMessage('Dummy message', $truncate);
  $entry->setSeverity($severity_threshold);
  $entry->setSubType('200 - TEST entry');
  $entry->setMethod('POST');
  $entry->setRequest_uri($GLOBALS['base_root'] . Drupal::request()->getRequestUri());
  $entry->setReferer('');
  $entry->setAccount(Drupal::currentUser());
  $entry->setClient_ip(Drupal::request()->getClientIp());
  $entry->setLink('');

  $example_data = jsonlog_example_entry($entry);
  $form['jsonlog']['examples']['example_entry'] = [
    '#type' => 'table',
    '#id' => 'jsonlog-examples',
    '#sticky' => TRUE,
    '#attributes' => [
      'class' => [
        'jsonlog-entry-example',
      ],
    ],
    '#header' => $example_data['#header'],
    '#rows' => $example_data['#rows'],
  ];

  // This data is being used during submit if we need to write a test-entry to log
  $form['jsonlog']['examples']['example_entry_data']  = [
    '#type' => 'hidden',
    '#value' => $entry->getData(),
  ];

  $form['jsonlog']['test_entry'] = [
    '#type' => 'checkbox',
    '#title' => t('Write above test-entry to log file', [], ['context' => 'module:jsonlog']),
    '#description' => t('Save first and then perform a test to make sure your settings are applied.', [], ['context' => 'module:jsonlog']),
  ];

  $form['#validate'][] = 'jsonlog_form_system_logging_settings_validate';

  // Prepend our submit handler; we need to get in first, otherwise our changes
  // to form values amounts to nothing.
  array_unshift($form['#submit'], 'jsonlog_form_system_logging_settings_submit');
}

/**
 * Establish default site ID.
 *
 * Server's hostname + __ + (default) database name.
 * If default database also has a prefix: + __ + database prefix.
 *
 * @return string
 */
function jsonlog_default_site_id() {
  if (($site_id = getenv('drupal_jsonlog_siteid'))) {
    return $site_id;
  }

  $db_info = Database::getConnectionInfo();
  // Server's hostname + database name + database prefix (if any).
  $site_id = strtolower(preg_replace('/[^\w\d\.\-_]/', '-', gethostname()))
    . '__' . $db_info['default']['database']
    . ($db_info['default']['prefix'] && !empty($db_info['default']['prefix']['default']) ? ('__' . $db_info['default']['prefix']['default']) : '');
  unset($db_info); // Clear ref.

  return $site_id;
}

/**
 * Establish default logging dir.
 *
 * Directory default:
 * PHP:ini error_log - unless that is 'syslog' (then checks the usual suspects
 * /var/log/...) - plus /drupal-jsonlog.
 *
 * @return string
 *   Empty upon failure.
 */
function jsonlog_default_dir() {
  if (($dir = getenv('drupal_jsonlog_dir'))) {
    return $dir;
  }

  // Default web server log dir for common *nix distributions.
  $default_log_dirs = [
    'debian' => '/var/log/apache2',
    'redhat' => '/var/log/httpd',
  ];

  $dir = '';
  if (!($server_log = ini_get('error_log')) || $server_log === 'syslog') {
    // Try default web server log dirs for common *nix distributions.
    foreach ($default_log_dirs as $val) {
      if (file_exists($val)) {
        $dir = $val;
        break;
      }
    }
  }
  else {
    $dir = str_replace('\\', '/', dirname($server_log));
  }

  if ($dir) {
    return $dir . '/drupal-jsonlog';
  }
  return '';
}

/**
 * Helper function to gather data as an example in admin screen
 *
 * @param \Drupal\jsonlog\Logger\JsonLogData $entry
 * @return array with table #header and #rows
 */
function jsonlog_example_entry($entry) {
  // JSON fields.
  $json_fields = [
    'message' => [
      'label' => t('Message', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['message'],
    ],
    '@timestamp' => [
      'label' => t('Timestamp', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['@timestamp'],
    ],
    '@version' => [
      'label' => t('Version', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['@version'],
    ],
    'message_id' => [
      'label' => t(
        'Message ID (@site_id + unique ID)',
        ['@site_id' => $entry->getData()['site_id']],
        ['context' => 'module:jsonlog']
      ),
      'value' => $entry->getData()['message_id'],
    ],
    'site_id' => [
      'label' => t('Site ID', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['site_id'],
    ],
    'canonical' => [
      'label' => t('Canonical', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['canonical'],
    ],
    'tags' => [
      'label' => t('Tags (null or array of strings)', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['tags'],
    ],
    'type' => [
      'label' => t('Type', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['type'],
    ],
    'subtype' => [
      'label' => t('Subtype (Rfc \'type\')', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['subtype'],
    ],
    'severity' => [
      'label' => t('Severity', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['severity'],
    ],
    'method' => [
      'label' => t('Request method (GET, POST, cli)', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['method'],
    ],
    'request_uri' => [
      'label' => t('Request URI', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['request_uri'],
    ],
    'referer' => [
      'label' => t('Referer', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['referer'],
    ],
    'uid' => [
      'label' => t('User ID', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['uid'],
    ],
    'username' => [
      'label' => t('Username (when not anonymous user)', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['username'],
    ],
    'client_ip' => [
      'label' => t('User\'s I.P. address', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['client_ip'],
    ],
    'link' => [
      'label' => t('Link (URL path or NULL)', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['link'],
    ],
    'code' => [
      'label' => t('Code (positive integer if watchdog \'link\' is N or \'N\')', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['code'],
    ],
    'trunc' => [
      'label' => t('Truncation (null, or array [original message length, truncated message length])', [], ['context' => 'module:jsonlog']),
      'value' => $entry->getData()['trunc'],
    ],
  ];

  $table_header = [
    t('Property', [], ['context' => 'module:jsonlog']),
    t('JSON field name', [], ['context' => 'module:jsonlog']),
    t('Example', [], ['context' => 'module:jsonlog']),
  ];

  $table_rows = [];
  foreach ($json_fields as $name => $props) {
    $table_rows[] = [
      'data' => [
        $props['label'],
        $name,
        $props['value'] === NULL ? 'null' : $props['value'],
      ],
    ];
  }

  return ['#header' => $table_header, '#rows' => $table_rows];
}
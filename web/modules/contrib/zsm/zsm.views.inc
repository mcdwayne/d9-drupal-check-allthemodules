<?php

/**
 * @file
 */


/**
 * Implements hook_views_data().
 */

function zsm_views_data() {
    $data['zsm_core'] = [];
    $data['zsm_core']['table'] = [];
    $data['zsm_core']['table']['group'] = 'ZSM Core Settings';
    $data['zsm_core']['table']['provider'] = 'zsm';
    $data['zsm_core']['table']['base'] = array(
        'field' => 'id',
        'title' => t('ZSM Core Settings'),
        'help' => t('ZSM Core Settings Items'),
        'weight' => -10,
    );

    /**
     * Basics
     */
    $data['zsm_core']['id'] = [
        'title' => t('ID'),
        'help' => t('Numeric ID of the ZSM Core Settings'),
        'field' => ['id' => 'numeric'],
        'sort' => ['id' => 'standard'],
        'filter' => ['id' => 'numeric'],
        'argument' => ['id' => 'numeric'],
    ];
    $data['zsm_core']['uuid'] = [
        'title' => t('UUID'),
        'help' => t('UUID of the ZSM Core Settings'),
        'field' => ['id' => 'standard'],
        'sort' => ['id' => 'standard'],
        'filter' => ['id' => 'string'],
        'argument' => ['id' => 'string'],
    ];
    $data['zsm_core']['created'] = [
        'title' => t('Post Date'),
        'help' => t('Moment the ZSM Core Settings were created'),
        'field' => ['id' => 'date'],
        'sort' => ['id' => 'date'],
        'filter' => ['id' => 'date'],
    ];
    $data['zsm_core']['changed'] = [
        'title' => t('Updated Date'),
        'help' => t('Moment the ZSM Core Settings were last updated'),
        'field' => ['id' => 'date'],
        'sort' => ['id' => 'date'],
        'filter' => ['id' => 'date'],
    ];
    $data['zsm_core']['title'] = [
        'title' => t('Title'),
        'help' => t('Title of the ZSM Instance'),
        'field' => ['id' => 'standard'],
        'sort' => ['id' => 'standard'],
        'filter' => ['id' => 'string'],
        'argument' => ['id' => 'string'],
    ];
    $data['zsm_core']['user_id'] = [
        'title' => t('Author UID'),
        'help' => t('Numeric ID of the ZSM Instance'),
        'field' => ['id' => 'numeric'],
        'sort' => ['id' => 'standard'],
        'filter' => ['id' => 'numeric'],
        'argument' => ['id' => 'numeric'],
    ];
    /**
     * Metadata
     */
    $data['zsm_core']['description'] = [
        'title' => t('Description'),
        'help' => t('Description of the ZSM Instance. Used in the report metadata.'),
        'field' => ['id' => 'standard'],
        'sort' => ['id' => 'standard'],
        'filter' => ['id' => 'string'],
        'argument' => ['id' => 'string'],
    ];

    /**
     * Verbosity/Logging
     */
    $data['zsm_core']['verbosity'] = [
        'title' => t('Verbosity'),
        'help' => t('CLI Verbosity for ZSM Instance'),
        'field' => ['id' => 'numeric'],
        'sort' => ['id' => 'standard'],
        'filter' => ['id' => 'numeric'],
        'argument' => ['id' => 'numeric'],
    ];
    $data['zsm_core']['log_verbosity'] = [
        'title' => t('Log Verbosity'),
        'help' => t('Log Verbosity for ZSM Instance'),
        'field' => ['id' => 'numeric'],
        'sort' => ['id' => 'standard'],
        'filter' => ['id' => 'numeric'],
        'argument' => ['id' => 'numeric'],
    ];
    $data['zsm_core']['log_path'] = [
        'title' => t('Log Path'),
        'help' => t('Path to the log file.'),
        'field' => ['id' => 'standard'],
        'sort' => ['id' => 'standard'],
        'filter' => ['id' => 'string'],
        'argument' => ['id' => 'string'],
    ];

    /**
     * Save State
     */
    $data['zsm_core']['load_previous_state'] = [
        'title' => t('Load Previous State?'),
        'help' => t('Whether to load previously saved data'),
        'field' => ['id' => 'boolean'],
        'sort' => ['id' => 'standard'],
        'filter' => [
            'id' => 'boolean',
            'label' => t('Load Previous State?'),
            'type' => 'yes-no',
            'use_equal' => TRUE
        ],
    ];
    $data['zsm_core']['save_on_shutdown'] = [
        'title' => t('Save on Shutdown?'),
        'help' => t('Whether to save collected data on shutdown.'),
        'field' => ['id' => 'boolean'],
        'sort' => ['id' => 'standard'],
        'filter' => [
            'id' => 'boolean',
            'label' => t('Save on Shutdown?'),
            'type' => 'yes-no',
            'use_equal' => TRUE
        ],
    ];
    $data['zsm_core']['state_filename'] = [
        'title' => t('State Filename'),
        'help' => t('File name of the file containing a save state in JSON format.'),
        'field' => ['id' => 'standard'],
        'sort' => ['id' => 'standard'],
        'filter' => ['id' => 'string'],
        'argument' => ['id' => 'string'],
    ];

    /**
     * Custom Path Data
     */
    $data['zsm_core']['path_configs'] = [
        'title' => t('Path to Configurations Directory'),
        'help' => t('Absolute path to a custom configs directory.'),
        'field' => ['id' => 'standard'],
        'sort' => ['id' => 'standard'],
        'filter' => ['id' => 'string'],
        'argument' => ['id' => 'string'],
    ];
    $data['zsm_core']['path_data'] = [
        'title' => t('Path to Data Directory'),
        'help' => t('Absolute path to a custom data directory.'),
        'field' => ['id' => 'standard'],
        'sort' => ['id' => 'standard'],
        'filter' => ['id' => 'string'],
        'argument' => ['id' => 'string'],
    ];
    $data['zsm_core']['path_logs'] = [
        'title' => t('Path to Logs Directory'),
        'help' => t('Absolute path to a custom logs directory.'),
        'field' => ['id' => 'standard'],
        'sort' => ['id' => 'standard'],
        'filter' => ['id' => 'string'],
        'argument' => ['id' => 'string'],
    ];
    $data['zsm_core']['path_plugins'] = [
        'title' => t('Path to Plugins Directory'),
        'help' => t('Absolute path to a custom plugins directory.'),
        'field' => ['id' => 'standard'],
        'sort' => ['id' => 'standard'],
        'filter' => ['id' => 'string'],
        'argument' => ['id' => 'string'],
    ];

  /**
   * Enabled Plugins
   */
  $data['zsm_core']['manage_enabled_plugins'] = [
    'title' => t('Manage Enabled Plugins'),
    'help' => t('Renders enabled plugins with management options.'),
    'field' => ['id' => 'zsm_manage_enabled_plugins'],
  ];

    return $data;
}


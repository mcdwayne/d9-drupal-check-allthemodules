<?php

namespace Drupal\users_export\Form;

use AKlump\LoftDataGrids\ExportData;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides UsersExport Form.
 */
class UsersExportForm extends ConfigFormBase {

  private $dateFormatter;
  private $dateFormatStorage;

  /**
   * UsersExportForm constructor.
   */
  public function __construct(DateFormatterInterface $date_formatter, EntityStorageInterface $date_format_storage) {
    $this->dateFormatter = $date_formatter;
    $this->dateFormatStorage = $date_format_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity.manager')->getStorage('date_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'users_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('users_export.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Form constructor for the users export form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = \Drupal::config('users_export.settings');
    $core = \Drupal::service('loft_data_grids.core');
    $options = $core->getExporterOptions(TRUE, FALSE, FALSE);

    // This does NOT work for library_load()
    $form['#attached']['library'][] = 'users_export/core';

    $exporters = $core->getExporters();
    $form_state->set('exporters', $exporters);

    $jsSettings = [];
    foreach ($exporters as $exporter) {
      $jsSettings[$exporter['id']] = array_intersect_key($exporter, array_flip(['extension']));
    }
    $form['#attached']['drupalSettings']['usersExport'] = $jsSettings;

    $class = $settings->get('users_export_type');
    $type = $exporters[$class]['extension'];
    $form['users_export_type'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Export file format'),
      '#default_value' => $class,
      '#options'       => $options,
    ];

    $default = $settings->get('users_export_filename');
    if (empty($default)) {
      if (!($name = \Drupal::config('system.site')->get('name'))) {
        $name = 'users_export';
      }
      $default = strtolower(preg_replace('/\W+/', '_', $name) . '_users');
    }
    $form['users_export_filename'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Filename to save as'),
      '#default_value' => $default,
      '#required'      => TRUE,
      '#field_suffix'  => $type,
    ];

    $test_mode = $settings->get('users_export_test_mode');
    $form['users_export_test_mode'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Preview mode (Enable to limit the export to only the first 10 users to check formatting.)'),
      '#default_value' => $test_mode,
    ];

    $form['basic_filters'] = [
      '#type'  => 'details',
      '#title' => $this->t('Basic Filters'),
      '#open'  => FALSE,
    ];

    $form['advanced'] = [
      '#type'  => 'details',
      '#title' => $this->t('Advanced Settings'),
      '#open'  => FALSE,
    ];

    $form['basic_filters']['users_export_with_access'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Login Frequency'),
      '#default_value' => $settings->get('users_export_with_access'),
      '#options'       => [
        2 => $this->t('All Users'),
        1 => $this->t('Get users who have logged in at least one time'),
        0 => $this->t('Get users who have never logged in'),
      ],
    ];

    // Add field to add 'blocked' users to the export as well.
    $form['basic_filters']['users_export_user_status'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Export users with status'),
      '#default_value' => $settings->get('users_export_with_access'),
      '#options'       => [
        1 => $this->t('Active'),
        0 => $this->t('Blocked'),
        2 => $this->t('Both active and blocked'),
      ],
    ];

    // Option to Filter By Role. (Optional).
    $form['basic_filters']['users_export_filter_by_role'] = [
      '#type'          => 'radios',
      '#title'         => $this->t('Filter by role?'),
      '#default_value' => $settings->get('users_export_filter_by_role'),
      '#options'       => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => 0,
    ];

    $roles = user_roles(TRUE);
    $roles_options = [];

    foreach ($roles as $role) {
      $role_id = $role->id();
      $role_label = $role->label();
      $roles_options[$role_id] = $role_label;
    }

    $form['basic_filters']['users_export_user_role'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Export users with role'),
      '#default_value' => $settings->get('users_export_with_role'),
      '#options' => $roles_options,
      '#default_value' => array_keys($roles_options),
      '#states' => [
        'invisible' => [
          ':input[name="users_export_filter_by_role"]' => ['value' => 0],
        ],
      ],
    ];

    $form['basic_filters']['users_export_order'] = [
      '#type' => 'select',
      '#title' => $this->t('Order of results'),
      '#default_value' => $settings->get('users_export_order'),
      '#options' => [
        0 => $this->t('User ID'),
        1 => $this->t('Username A-Z'),
        2 => $this->t('E-mail A-Z'),
      ],
    ];

    $time = new DrupalDateTime();
    $format_types = $this->dateFormatStorage->loadMultiple();
    $options = [];
    foreach ($format_types as $type => $type_info) {
      $format = $this->dateFormatter->format($time->format('U'), $type);
      $options[$type_info->getPattern()] = $type_info->label() . ' (' . $format . ')';
    }

    // Now add in those we think most users will want to use.
    $options += [
      'Y-m-d'            => 'Excel Date (' . date('Y-m-d') . ')',
      'Y-m-d H:i:s'      => 'Excel Date & Time (' . date('Y-m-d H:i:s') . ')',
      'Y-m-d\TH:i:s\Z'   => 'Datetime (' . date('Y-m-d\TH:i:s\Z') . ')',
      'U'                => 'Unix Timestamp (' . date('U') . ')',
      \DateTime::ISO8601 => 'ISO 8601 (' . date(\DateTime::ISO8601) . ')',
      'Ymd\THis'         => 'iCal (' . date('Ymd\THis') . ')',
    ];

    $form['advanced']['users_export_date_format'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Date format'),
      '#default_value' => $settings->get('users_export_date_format'),
      '#options'       => $options,
    ];

    $options = [-1 => $this->t('- Default -')];
    foreach (range(128, 2048, 32) as $value) {
      $options[$value . 'M'] = format_size($value * 1024 * 1024);
    }
    $form['advanced']['users_export_memory_limit'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Memory Limit', [
        '@size' => format_size($settings->get('users_export_last_export_memory')),
      ]),
      '#description'   => $this->t('If you have many users you may need to set this value higher so you the web server does not run out of memory processing the export. <strong>Depending upon your server configuration, this may or may not have any effect!</strong> For more information refer to <a href="http://php.net/manual/en/function.ini-set.php" target="blank">http://php.net/manual/en/function.ini-set.php</a>.'),
      '#default_value' => $settings->get('users_export_memory_limit'),
      '#options'       => $options,
    ];

    $options = [-1 => $this->t('- Default -')];
    foreach (range(30, 1800, 30) as $value) {
      $options[$value] = \Drupal::service("date.formatter")
        ->formatInterval($value);
    }
    $form['advanced']['users_export_max_execution'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Maximum Execution Time', [
        '@time' => \Drupal::service("date.formatter")
          ->formatInterval($settings->get('users_export_last_export_time')),
      ]),
      '#description'   => $this->t('If you have many users you may need to set this value higher so you the web server does not timeout. <strong>Depending upon your server configuration, this may or may not have any effect!</strong> For more information refer to <a href="http://php.net/manual/en/function.set-time-limit.php" target="blank">http://php.net/manual/en/function.set-time-limit.php</a>.'),
      '#default_value' => $settings->get('users_export_max_execution'),
      '#options'       => $options,
    ];

    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = $this->t('Download File');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['users_export.settings'];
  }

  /**
   * Manage form submition.
   *
   * @codingStandardsIgnoreStart
   */
  public function _submitForm(array &$form, FormStateInterface $form_state) {

    //@codingStandardsIgnoreEnd
    $values = $form_state->getValues();
    if ($values['users_export_memory_limit'] != -1) {
      ini_set('memory_limit', $values['users_export_memory_limit']);
    }

    if ($values['users_export_max_execution'] != -1) {
      set_time_limit($values['users_export_max_execution']);
    }

    if ($values['users_export_filter_by_role'] == 1 && !empty($values['users_export_user_role'])) {
      $values['users_export_user_role'] = array_filter($values['users_export_user_role']);
    }
    else {
      $values['users_export_user_role'] = FALSE;
    }

    $exporters = $form_state->get('exporters');
    $exporter = $exporters[$values['users_export_type']]['class'];
    $exporter = new $exporter(new ExportData(), $values['users_export_filename']);

    \Drupal::service('users_export.core')->exporterLoadUsers($exporter, [
      'with_access' => $values['users_export_with_access'],
      'limit'       => $values['users_export_test_mode'] ? 10 : NULL,
      'order'       => $values['users_export_order'],
      'roles'       => $values['users_export_user_role'],
      'status'      => ($s = $values['users_export_user_status']) === 2 ? NULL : $s * 1,
      'date_format' => $values['users_export_date_format'],
    ]);

    // TODO Should this be wrapped in a Response object?
    // https://www.drupal.org/node/2017339
    // https://www.drupal.org/node/1623114
    $exporter->save();
  }

}

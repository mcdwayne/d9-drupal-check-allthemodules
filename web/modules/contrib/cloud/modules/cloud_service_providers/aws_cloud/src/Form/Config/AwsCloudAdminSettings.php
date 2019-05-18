<?php

namespace Drupal\aws_cloud\Form\Config;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\aws_cloud\Service\GoogleSpreadsheetService;
use Drupal\cloud\Plugin\CloudConfigPluginManagerInterface;
use Drupal\Core\Config\Config;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AWS Cloud Admin Settings.
 */
class AwsCloudAdminSettings extends ConfigFormBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * The google spreadsheet service.
   *
   * @var \Drupal\aws_cloud\Service\GoogleSpreadsheetService
   */
  private $googleSpreadsheetService;

  /**
   * The cloud config plugin manager.
   *
   * @var \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface
   */
  private $cloudConfigPluginManager;

  /**
   * Constructs a AwsCloudAdminSettings object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\aws_cloud\Service\GoogleSpreadsheetService $google_spreadsheet_service
   *   The google spreadsheet service.
   * @param \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface $cloud_config_plugin_manager
   *   The cloud config plugin manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    FileSystemInterface $file_system,
    GoogleSpreadsheetService $google_spreadsheet_service,
    CloudConfigPluginManagerInterface $cloud_config_plugin_manager
  ) {
    parent::__construct($config_factory);

    $this->fileSystem = $file_system;
    $this->googleSpreadsheetService = $google_spreadsheet_service;
    $this->cloudConfigPluginManager = $cloud_config_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('file_system'),
      $container->get('aws_cloud.google_spreadsheet'),
      $container->get('plugin.manager.cloud_config_plugin')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aws_cloud_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['aws_cloud.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('aws_cloud.settings');

    $form['test_mode'] = [
      '#type' => 'details',
      '#title' => $this->t('Test Mode'),
      '#open' => TRUE,
    ];

    $form['test_mode']['aws_cloud_test_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable test mode?'),
      '#default_value' => $config->get('aws_cloud_test_mode'),
      '#description' => $this->t('This enables you to test the AWS Cloud module settings without accessing AWS.'),
    ];

    $form['views'] = [
      '#type' => 'details',
      '#title' => $this->t('Views'),
      '#open' => TRUE,
      '#description' => $this->t("Note that selecting the default option will overwrite View's settings."),
    ];

    $form['views']['refresh_options'] = [
      '#type' => 'details',
      '#title' => $this->t('View refresh interval'),
      '#open' => TRUE,
    ];

    $form['views']['refresh_options']['aws_cloud_view_refresh_interval'] = [
      '#type' => 'number',
      '#description' => $this->t('Refresh content of views at periodical intervals.'),
      '#default_value' => $config->get('aws_cloud_view_refresh_interval'),
      '#min' => 1,
      '#max' => 9999,
      '#field_suffix' => 'seconds',
    ];

    $form['views']['pager_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Pager options'),
      '#open' => TRUE,
    ];

    $form['views']['pager_options']['aws_cloud_view_expose_items_per_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow user to control the number of items displayed in views.'),
      '#default_value' => $config->get('aws_cloud_view_expose_items_per_page'),
      '#description' => $this->t('When enabled, an "Items per page" dropdown listbox is shown.'),
    ];

    $form['views']['pager_options']['aws_cloud_view_items_per_page'] = [
      '#type' => 'select',
      '#options' => aws_cloud_get_views_items_options(),
      '#title' => $this->t('Items per page'),
      '#description' => $this->t('Number of items to display on each page in views.'),
      '#default_value' => $config->get('aws_cloud_view_items_per_page'),
    ];

    $form['instance'] = [
      '#type' => 'details',
      '#title' => $this->t('Instance'),
      '#open' => TRUE,
    ];

    $form['instance']['notification_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Notification Settings'),
      '#open' => TRUE,
    ];

    $form['instance']['notification_settings']['aws_cloud_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable instance notification'),
      '#description' => $this->t('When enabled, instance owners or admins will be notified if their instance has been running for too long.'),
      '#default_value' => $config->get('aws_cloud_notification'),
    ];

    $form['instance']['notification_settings']['aws_cloud_notify_owner'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify owner'),
      '#description' => $this->t('When selected, instance owners will be notified.'),
      '#default_value' => $config->get('aws_cloud_notify_owner'),
    ];

    $form['instance']['notification_settings']['aws_cloud_notification_frequency'] = [
      '#type' => 'select',
      '#options' => [
        86400 => $this->t('Once a day'),
        604800 => $this->t('Once every 7 days'),
        2592000 => $this->t('Once every 30 days'),
      ],
      '#title' => $this->t('Notification frequency'),
      '#description' => $this->t('Instance owners will be notified once per option selected'),
      '#default_value' => $config->get('aws_cloud_notification_frequency'),
    ];

    $form['instance']['notification_settings']['aws_cloud_notification_criteria'] = [
      '#type' => 'select',
      '#options' => [
        1 => $this->t('1 day'),
        30 => $this->t('30 days'),
        60 => $this->t('60 days'),
        90 => $this->t('90 days'),
      ],
      '#title' => $this->t('Notification criteria'),
      '#description' => $this->t('Notify instance owners after an instance has been running for this period of time'),
      '#default_value' => $config->get('aws_cloud_notification_criteria'),
    ];

    $form['instance']['notification_settings']['aws_cloud_instance_notification_fields'] = [
      '#type' => 'fieldgroup',
    ];

    $form['instance']['notification_settings']['aws_cloud_instance_notification_fields']['aws_cloud_instance_notification_title'] = [
      '#type' => 'item',
      '#title' => $this->t('Notification time'),
    ];

    $form['instance']['notification_settings']['aws_cloud_instance_notification_fields']['aws_cloud_instance_notification_hour'] = [
      '#type' => 'select',
      '#prefix' => '<div class="container-inline">',
      '#options' => $this->getDigits(24),
      '#default_value' => $config->get('aws_cloud_instance_notification_hour'),
    ];

    $form['instance']['notification_settings']['aws_cloud_instance_notification_fields']['aws_cloud_instance_notification_minutes'] = [
      '#prefix' => ': ',
      '#type' => 'select',
      '#options' => $this->getDigits(60),
      '#default_value' => $config->get('aws_cloud_instance_notification_minutes'),
      '#suffix' => '</div>' . $this->t('Time to send the instance usage email.'),
    ];

    $form['instance']['email_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Email Settings'),
      '#open' => TRUE,
    ];

    $form['instance']['email_settings']['aws_cloud_instance_notification_emails'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email addresses'),
      '#description' => $this->t('Email addresses to be notified.  Emails can be comma separated.'),
      '#default_value' => $config->get('aws_cloud_instance_notification_emails'),
    ];

    $form['instance']['email_settings']['aws_cloud_notification_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('Edit the email subject.'),
      '#default_value' => $config->get('aws_cloud_notification_subject'),
    ];

    $form['instance']['email_settings']['aws_cloud_notification_msg'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email message'),
      '#default_value' => $config->get('aws_cloud_notification_msg'),
      '#description' => $this->t('Available tokens are: [aws_cloud_instance:instances], [site:url].  The [aws_cloud_instance:instances] variable can be configured in the Instance information below.'),
    ];

    $form['instance']['email_settings']['aws_cloud_instance_notification_instance_info'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Instance information'),
      '#default_value' => $config->get('aws_cloud_instance_notification_instance_info'),
      '#description' => $this->t('More than one instance can appear in the email message. Available tokens are: [aws_cloud_instance:name], [aws_cloud_instance:id], [aws_cloud_instance:launch_time], [aws_cloud_instance:instance_state], [aws_cloud_instance:availability_zone], [aws_cloud_instance:private_ip], [aws_cloud_instance:public_up], [aws_cloud_instance:elastic_ip], [aws_cloud_instance:instance_link], [aws_cloud_instance:instance_edit_link]'),
    ];

    $form['volume'] = [
      '#type' => 'details',
      '#title' => $this->t('Volume'),
      '#open' => TRUE,
    ];

    $form['volume']['notification_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Notification Settings'),
      '#open' => TRUE,
    ];

    $form['volume']['notification_settings']['aws_cloud_volume_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable volume notification'),
      '#description' => $this->t('When enabled, an email will be sent if volumes are unused.  Additionally, the created date field will be marked in red on the Volume listing page and Volume detail page.'),
      '#default_value' => $config->get('aws_cloud_volume_notification'),
    ];

    $form['volume']['notification_settings']['aws_cloud_volume_notify_owner'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify owner'),
      '#description' => $this->t('When selected, volume owners will be notified.'),
      '#default_value' => $config->get('aws_cloud_volume_notify_owner'),
    ];

    $form['volume']['notification_settings']['aws_cloud_unused_volume_criteria'] = [
      '#type' => 'select',
      '#title' => $this->t('Unused volume criteria'),
      '#description' => $this->t('A volume is considered unused if it has been created and available for the specified number of days.'),
      '#options' => [
        30 => $this->t('30 days'),
        60 => $this->t('60 days'),
        90 => $this->t('90 days'),
        180 => $this->t('180 days'),
        365 => $this->t('One year'),
      ],
      '#default_value' => $config->get('aws_cloud_unused_volume_criteria'),
    ];

    $form['volume']['notification_settings']['aws_cloud_volume_notification_frequency'] = [
      '#type' => 'select',
      '#options' => [
        86400 => $this->t('Once a day'),
        604800 => $this->t('Once every 7 days'),
        2592000 => $this->t('Once every 30 days'),
      ],
      '#title' => $this->t('Notification frequency'),
      '#description' => $this->t('Volume notification will be sent once per option selected.'),
      '#default_value' => $config->get('aws_cloud_volume_notification_frequency'),
    ];

    $form['volume']['notification_settings']['aws_cloud_volume_notification_fields'] = [
      '#type' => 'fieldgroup',
    ];

    $form['volume']['notification_settings']['aws_cloud_volume_notification_fields']['aws_cloud_volume_notification_title'] = [
      '#type' => 'item',
      '#title' => $this->t('Notification time'),
    ];

    $form['volume']['notification_settings']['aws_cloud_volume_notification_fields']['aws_cloud_volume_notification_hour'] = [
      '#type' => 'select',
      '#prefix' => '<div class="container-inline">',
      '#options' => $this->getDigits(24),
      '#default_value' => $config->get('aws_cloud_volume_notification_hour'),
    ];

    $form['volume']['notification_settings']['aws_cloud_volume_notification_fields']['aws_cloud_volume_notification_minutes'] = [
      '#prefix' => ': ',
      '#type' => 'select',
      '#options' => $this->getDigits(60),
      '#default_value' => $config->get('aws_cloud_volume_notification_minutes'),
      '#suffix' => '</div>' . $this->t('Time to send the volume usage email.'),
    ];

    $form['volume']['email_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Email Settings'),
      '#open' => TRUE,
    ];

    $form['volume']['email_settings']['aws_cloud_volume_notification_emails'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email addresses'),
      '#description' => $this->t('Email addresses to be notified.  Emails can be comma separated.'),
      '#default_value' => $config->get('aws_cloud_volume_notification_emails'),
    ];

    $form['volume']['email_settings']['aws_cloud_volume_notification_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('Edit the email subject.'),
      '#default_value' => $config->get('aws_cloud_volume_notification_subject'),
    ];

    $form['volume']['email_settings']['aws_cloud_volume_notification_msg'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email message'),
      '#default_value' => $config->get('aws_cloud_volume_notification_msg'),
      '#description' => $this->t('Available tokens are: [aws_cloud_volume:volumes], [site:url].  The [aws_cloud_volume:volumes] variable can be configured in the Volume information below.'),
    ];

    $form['volume']['email_settings']['aws_cloud_volume_notification_volume_info'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Volume information'),
      '#default_value' => $config->get('aws_cloud_volume_notification_volume_info'),
      '#description' => $this->t('More than one volume can appear in the email message.  Available tokens are: [aws_cloud_volume:name], [aws_cloud_volume:volume_link], [aws_cloud_volume:created], [aws_cloud_volume:volume_edit_link]'),
    ];

    $form['snapshot'] = [
      '#type' => 'details',
      '#title' => $this->t('Snapshot'),
      '#open' => TRUE,
    ];

    $form['snapshot']['notification_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Notification Settings'),
      '#open' => TRUE,
    ];

    $form['snapshot']['notification_settings']['aws_cloud_snapshot_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable snapshot notification'),
      '#description' => $this->t('When enabled, an email will be sent if snapshot are unused.'),
      '#default_value' => $config->get('aws_cloud_snapshot_notification'),
    ];

    $form['snapshot']['notification_settings']['aws_cloud_snapshot_notify_owner'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify owner'),
      '#description' => $this->t('When selected, snapshot owners will be notified.'),
      '#default_value' => $config->get('aws_cloud_snapshot_notify_owner'),
    ];

    $form['snapshot']['notification_settings']['aws_cloud_snapshot_notification_frequency'] = [
      '#type' => 'select',
      '#options' => [
        86400 => $this->t('Once a day'),
        604800 => $this->t('Once every 7 days'),
        2592000 => $this->t('Once every 30 days'),
      ],
      '#title' => $this->t('Notification frequency'),
      '#description' => $this->t('Snapshot notification will be sent once per option selected.'),
      '#default_value' => $config->get('aws_cloud_snapshot_notification_frequency'),
    ];

    $form['snapshot']['notification_settings']['aws_cloud_snapshot_notification_fields'] = [
      '#type' => 'fieldgroup',
    ];

    $form['snapshot']['notification_settings']['aws_cloud_snapshot_notification_fields']['aws_cloud_snapshot_notification_title'] = [
      '#type' => 'item',
      '#title' => $this->t('Notification time'),
    ];

    $form['snapshot']['notification_settings']['aws_cloud_snapshot_notification_fields']['aws_cloud_snapshot_notification_hour'] = [
      '#type' => 'select',
      '#prefix' => '<div class="container-inline">',
      '#options' => $this->getDigits(24),
      '#default_value' => $config->get('aws_cloud_snapshot_notification_hour'),
    ];

    $form['snapshot']['notification_settings']['aws_cloud_snapshot_notification_fields']['aws_cloud_snapshot_notification_minutes'] = [
      '#prefix' => ': ',
      '#type' => 'select',
      '#options' => $this->getDigits(60),
      '#default_value' => $config->get('aws_cloud_snapshot_notification_minutes'),
      '#suffix' => '</div>' . $this->t('Time to send the snapshot usage email.'),
    ];

    $form['snapshot']['email_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Email Settings'),
      '#open' => TRUE,
    ];

    $form['snapshot']['email_settings']['aws_cloud_snapshot_notification_emails'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email addresses'),
      '#description' => $this->t('Email addresses to be notified.  Emails can be comma separated.'),
      '#default_value' => $config->get('aws_cloud_snapshot_notification_emails'),
    ];

    $form['snapshot']['email_settings']['aws_cloud_snapshot_notification_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email subject'),
      '#description' => $this->t('Edit the email subject.'),
      '#default_value' => $config->get('aws_cloud_snapshot_notification_subject'),
    ];

    $form['snapshot']['email_settings']['aws_cloud_snapshot_notification_msg'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email message'),
      '#default_value' => $config->get('aws_cloud_snapshot_notification_msg'),
      '#description' => $this->t('Available tokens are: [aws_cloud_snapshot:snapshots], [site:url].  The [aws_cloud_snapshot:snapshots] text is configured in the Snapshot information field below.'),
    ];

    $form['snapshot']['email_settings']['aws_cloud_snapshot_notification_snapshot_info'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Snapshot information'),
      '#default_value' => $config->get('aws_cloud_snapshot_notification_snapshot_info'),
      '#description' => $this->t('More than one snapshot can appear in the email message. Available tokens are: [aws_cloud_snapshot:name], [aws_cloud_snapshot:snapshot_link], [aws_cloud_snapshot:created], [aws_cloud_snapshot:snapshot_edit_link]'),
    ];

    $form['schedule'] = [
      '#type' => 'details',
      '#title' => $this->t('Schedule'),
      '#open' => TRUE,
    ];

    $form['schedule']['termination_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Termination Options'),
      '#open' => TRUE,
    ];

    $form['schedule']['termination_options']['aws_cloud_instance_terminate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically terminate instance'),
      '#description' => $this->t('Terminate instance automatically.'),
      '#default_value' => $config->get('aws_cloud_instance_terminate'),
    ];

    $form['schedule']['schedule_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('AWS Instance Scheduler'),
      '#open' => TRUE,
    ];

    $form['schedule']['schedule_settings']['aws_cloud_scheduler_tag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Schedule Tag'),
      '#description' => $this->t('Name of scheduling tag. This tag value is defined when setting up the <a href=":stack">AWS Instance Scheduler</a>', [
        ':stack' => 'https://docs.aws.amazon.com/solutions/latest/instance-scheduler/deployment.html',
      ]),
      '#default_value' => $config->get('aws_cloud_scheduler_tag'),
    ];

    $form['schedule']['schedule_settings']['aws_cloud_scheduler_periods'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Schedule periods'),
      '#description' => $this->t('<p>Schedules defined in AWS Instance Scheduler. The values entered are shown in the Schedule field on instance edit form and server template launch form. Enter one value per line, in the format <strong>key|label</strong>.</p><p>The key corresponds to the schedule name defined in AWS Instance Scheduler. The label is a free form descriptive value shown to users. An example configuration might be:<br/>office-hours|Office Hours - Monday to Friday 9:00am - 5:00pm.<br/><p>See <a href=:stack>Scheduler Configuration</a> for more information.</p>', [
        ':stack' => 'https://docs.aws.amazon.com/solutions/latest/instance-scheduler/components.html',
      ]),
      '#default_value' => $config->get('aws_cloud_scheduler_periods'),
    ];

    $form['cost_management'] = [
      '#type' => 'details',
      '#title' => $this->t('Cost Management'),
      '#open' => TRUE,
    ];

    $form['cost_management']['aws_cloud_instance_type_prices'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Instace Type Prices'),
      '#description' => $this->t('Enable Instance Type Prices.'),
      '#default_value' => $config->get('aws_cloud_instance_type_prices'),
    ];

    $form['cost_management']['aws_cloud_instance_type_prices_spreadsheet'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Instace Type Prices Spreadsheet'),
      '#description' => $this->t('Enable Instance Type Prices Spreadsheet.'),
      '#default_value' => $config->get('aws_cloud_instance_type_prices_spreadsheet'),
    ];

    $form['cost_management']['google'] = [
      '#type' => 'details',
      '#title' => $this->t('Google'),
      '#open' => TRUE,
    ];

    $form['cost_management']['google']['google_credential'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Google Credential'),
      '#description' => $this->t("The credential data of a service account."),
      '#rows' => 15,
    ];

    $form['cost_management']['google']['google_credential_file_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Credential File Path'),
      '#description' => $this->t(
        "The path of a service account's credential file. The default path is @path.",
        ['@path' => aws_cloud_google_credential_file_default_path()]
      ),
      '#default_value' => $config->get('google_credential_file_path'),
    ];

    $form['cost_management']['aws_cloud_instance_type_cost'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Instace Type Cost'),
      '#description' => $this->t('Enable Instance Type Cost in Server Template New or Edit Form.'),
      '#default_value' => $config->get('aws_cloud_instance_type_cost'),
    ];

    $form['cost_management']['aws_cloud_instance_type_cost_list'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Instace Type Cost List'),
      '#description' => $this->t('Enable Instance Type Cost List in Server Template Launch Form.'),
      '#default_value' => $config->get('aws_cloud_instance_type_cost_list'),
    ];

    $form['cost_management']['aws_cloud_instance_list_cost_column'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Instace List Cost Column'),
      '#description' => $this->t('Enable Cost Column in Instance List.'),
      '#default_value' => $config->get('aws_cloud_instance_list_cost_column'),
    ];

    $form['#attached']['library'][] = 'aws_cloud/aws_cloud_view_builder';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('aws_cloud.settings');
    $old_config = clone $config;
    $form_state->cleanValues();
    $views_settings = [];

    $old_credential_file_path = $config->get('google_credential_file_path');

    $excluded_keys = [];
    if ($form_state->getValue('aws_cloud_instance_type_prices_spreadsheet') == FALSE) {
      $excluded_keys[] = 'google_credential';
      $excluded_keys[] = 'google_credential_file_path';
    }

    $instance_time = '';
    $volume_time = '';
    $snapshot_time = '';
    foreach ($form_state->getValues() as $key => $value) {
      if (in_array($key, $excluded_keys)) {
        continue;
      }

      if ($key == 'aws_cloud_view_items_per_page') {
        $views_settings[$key] = (int) $value;
      }
      elseif ($key == 'aws_cloud_view_expose_items_per_page') {
        $views_settings[$key] = (boolean) $value;
      }

      if ($key == 'aws_cloud_instance_notification_hour') {
        $instance_time .= Html::escape($value);
      }
      if ($key == 'aws_cloud_instance_notification_minutes') {
        $instance_time .= ':' . Html::escape($value);
      }

      if ($key == 'aws_cloud_volume_notification_hour') {
        $volume_time .= Html::escape($value);
      }
      if ($key == 'aws_cloud_volume_notification_minutes') {
        $volume_time .= ':' . Html::escape($value);
      }

      if ($key == 'aws_cloud_snapshot_notification_hour') {
        $snapshot_time .= Html::escape($value);
      }
      if ($key == 'aws_cloud_snapshot_notification_minutes') {
        $snapshot_time .= ':' . Html::escape($value);
      }

      // If 'google_credential_file_path' is specified, store the
      // signature of the JSON file at 'google_credential_file_path'.
      // If 'google_credential' w/ the credentail value is specfied,
      // use the signature of the 'google_credential'.
      if ($key == 'google_credential') {
        if (empty($value)) {
          $value = file_get_contents(
            $form_state->getValue('google_credential_file_path')
          );
        }

        if (!empty($value)) {
          $config->set(
            'google_credential_signature',
            hash('sha256', json_encode(json_decode($value)))
          );
        }
        continue;
      }

      $config->set($key, Html::escape($value));
    }

    if (!empty($instance_time)) {
      // Add seconds into the instance time.
      $config->set('aws_cloud_instance_notification_time', $instance_time . ':00');
    }

    if (!empty($volume_time)) {
      // Add seconds into the volume time.
      $config->set('aws_cloud_volume_notification_time', $volume_time . ':00');
    }

    if (!empty($snapshot_time)) {
      // Add seconds into the snapshot time.
      $config->set('aws_cloud_snapshot_notification_time', $snapshot_time . ':00');
    }

    $config->save();

    if ($form_state->getValue('aws_cloud_instance_type_prices_spreadsheet') == TRUE) {
      if (!empty($form_state->getValue('google_credential'))) {
        $this->saveGoogleCredential(
          $form_state->getValue('google_credential'),
          $old_credential_file_path
        );
      }
    }

    if (!empty($views_settings)) {
      $this->updateViewsPagerOptions($views_settings);
    }

    parent::submitForm($form, $form_state);

    if ($this->shouldCacheBeCleaned($old_config, $config)) {
      drupal_flush_all_caches();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Don't validate if the spreadsheet of instance type prices is disabled.
    if ($form_state->getValue('aws_cloud_instance_type_prices_spreadsheet') == FALSE) {
      return;
    }

    $google_credential = $form_state->getValue('google_credential');
    if (empty($google_credential)) {
      $credential_file_path = $form_state->getValue('google_credential_file_path');
      if (empty($credential_file_path)) {
        $credential_file_path = aws_cloud_google_credential_file_default_path();
      }
      $config_credential_file_path = $this->config('aws_cloud.settings')->get('google_credential_file_path');
      if (empty($config_credential_file_path)) {
        $config_credential_file_path = aws_cloud_google_credential_file_default_path();
      }

      if ($config_credential_file_path == $credential_file_path
        && file_exists($config_credential_file_path)
      ) {
        return;
      }

      $form_state->setErrorByName(
        'google_credential',
        $this->t('The google credential is empty.')
      );
      return;
    }

    if (json_decode($google_credential) === NULL) {
      $form_state->setErrorByName(
        'google_credential',
        $this->t('The google credential is not valid json format.')
      );
    }
  }

  /**
   * Update views pager options.
   *
   * @param array $views_settings
   *   The key and value array of views pager options.
   */
  private function updateViewsPagerOptions(array $views_settings) {
    $views = [
      'views.view.aws_cloud_key_pairs',
      'views.view.aws_elastic_ip',
      'views.view.aws_images',
      'views.view.aws_instances',
      'views.view.aws_network_interfaces',
      'views.view.aws_security_group',
      'views.view.aws_snapshot',
      'views.view.aws_volume',
      'views.view.all_aws_cloud_instances',
    ];

    $options = [];
    foreach ($views_settings as $key => $value) {
      $view_key = str_replace('aws_cloud_view_', '', $key);
      if (strpos($view_key, 'expose_') !== FALSE) {
        $view_key = str_replace('expose_', 'expose.', $view_key);
        if ($value) {
          $items_per_page = aws_cloud_get_views_items_options();
          $options["display.default.display_options.pager.options.expose.items_per_page_options"] = implode(',', $items_per_page);
          $options["display.default.display_options.pager.options.expose.items_per_page_options_all"] = TRUE;
        }
      }
      $options["display.default.display_options.pager.options.$view_key"] = $value;

    }
    foreach ($views as $view_name) {
      aws_cloud_update_views_configuration($view_name, $options);
    }
  }

  /**
   * Helper function to generate values in the time drop down.
   *
   * @param int $max
   *   The maximum numbers to generate.
   *
   * @return array
   *   Array of time values.
   */
  private function getDigits($max) {
    $digits = [];
    for ($i = 0; $i < $max; $i++) {
      $digits[sprintf('%02d', $i)] = sprintf('%02d', $i);
    }
    return $digits;
  }

  /**
   * Save google credential to file.
   *
   * @param string $credential
   *   The credential data.
   * @param string $old_credential_file_path
   *   The old credential file path.
   */
  private function saveGoogleCredential($credential, $old_credential_file_path) {
    $credential_file_path = aws_cloud_google_credential_file_path();
    $credential_dir = $this->fileSystem->dirname($credential_file_path);
    if (file_prepare_directory(
      $credential_dir,
      FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS
    )) {
      // If credential changed, the old google spreadsheets should be deleted.
      $cloud_configs_changed = [];
      if ($this->isGoogleCredentialChanged($credential_file_path, $credential)) {
        $cloud_configs_changed = $this->deleteGoogleSpreadsheets();
      }

      file_unmanaged_save_data($credential, $credential_file_path, FILE_EXISTS_REPLACE);

      if (empty($old_credential_file_path)) {
        $old_credential_file_path = aws_cloud_google_credential_file_default_path();
      }

      // Remove old file.
      if ($old_credential_file_path != $credential_file_path
        && file_exists($old_credential_file_path)
      ) {
        file_unmanaged_delete($old_credential_file_path);
      }

      // Save cloud configs changed.
      // The spreadsheets belonging to them will updated by hook function.
      foreach ($cloud_configs_changed as $cloud_config) {
        $cloud_config->save();
      }
    }
  }

  /**
   * Check whether the google credential changed.
   *
   * @param string $credential_file_path
   *   The file path of google credential.
   * @param string $new_credential
   *   The new google credential content.
   *
   * @return bool
   *   Whether the google credential changed or not.
   */
  private function isGoogleCredentialChanged($credential_file_path, $new_credential) {
    if (!file_exists($credential_file_path)) {
      return TRUE;
    }

    $old_credential = file_get_contents($credential_file_path);
    if ($old_credential === FALSE) {
      return TRUE;
    }

    return trim($old_credential) !== trim($new_credential);
  }

  /**
   * Delete old google spreadsheets.
   *
   * @return array
   *   The cloud configs changed.
   */
  private function deleteGoogleSpreadsheets() {
    $cloud_configs = $this->cloudConfigPluginManager->loadConfigEntities('aws_ec2');
    $cloud_configs_changed = [];
    foreach ($cloud_configs as $cloud_config) {
      $old_url = $cloud_config->get('field_spreadsheet_pricing_url')->value;
      if (!empty($old_url)) {
        $this->googleSpreadsheetService->delete($old_url);
        $cloud_config->set('field_spreadsheet_pricing_url', '');
        $cloud_configs_changed[] = $cloud_config;
      }
    }

    return $cloud_configs_changed;
  }

  /**
   * Judge whether cache should be cleaned or not.
   *
   * @param \Drupal\Core\Config\Config $old_config
   *   The old config object.
   * @param \Drupal\Core\Config\Config $config
   *   The config object.
   *
   * @return bool
   *   Whether cache should be cleaned or not.
   */
  private function shouldCacheBeCleaned(Config $old_config, Config $config) {
    $items = [
      'aws_cloud_instance_type_prices',
      'aws_cloud_instance_type_prices_spreadsheet',
      'aws_cloud_instance_type_cost',
      'aws_cloud_instance_type_cost_list',
      'aws_cloud_instance_list_cost_column',
    ];

    foreach ($items as $item) {
      if ($old_config->get($item) != $config->get($item)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}

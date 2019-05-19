<?php
/**
 * @file
 * Contains \Drupal\tmgmt_smartling\SmartlingTranslatorUi.
 */

namespace Drupal\tmgmt_smartling;

use Drupal;
use Drupal\Core\Link;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\Url;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\Core\Form\FormStateInterface;
use Exception;
use Smartling\Jobs\JobStatus;

/**
 * Smartling translator UI.
 */
class SmartlingTranslatorUi extends TranslatorPluginUiBase {

  const TEMP_STORAGE_NAME = 'tmgmt_smartling_send_context';

  const USER_NAME_BEFORE_SWITCHING = 'user_name_before_switching';

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();

    $form['project_id'] = [
      '#type' => 'textfield',
      '#title' => t('Project Id'),
      '#default_value' => $translator->getSetting('project_id'),
      '#size' => 63,
      '#maxlength' => 25,
      '#required' => TRUE,
    ];

    $form['user_id'] = [
      '#type' => 'textfield',
      '#title' => t('User Id'),
      '#default_value' => $translator->getSetting('user_id'),
      '#size' => 63,
      '#maxlength' => 40,
      '#required' => TRUE,
    ];

    $form['token_secret'] = [
      '#type' => 'textfield',
      '#title' => t('Token Secret'),
      '#default_value' => $translator->getSetting('token_secret'),
      '#size' => 63,
      '#maxlength' => 65,
      '#required' => TRUE,
    ];

    $form['contextUsername'] = [
      '#type' => 'textfield',
      '#title' => t('Username for context retrieval'),
      '#size' => 63,
      '#maxlength' => 40,
      '#default_value' => $translator->getSetting('contextUsername'),
      '#required' => FALSE,
    ];

    $form['context_silent_user_switching'] = [
      '#type' => 'checkbox',
      '#title' => t('Context silent user authentication'),
      '#description' => t('If checked, Smartling won\'t trigger hook_login and hook_logout during user authentication for retrieving context.'),
      '#default_value' => $translator->getSetting('context_silent_user_switching'),
      '#required' => FALSE,
    ];

    $form['context_skip_host_verifying'] = [
      '#type' => 'checkbox',
      '#title' => t('Skip host verification'),
      '#description' => t('If checked, curl won\'t verify host during retrieving context (CURLOPT_SSL_VERIFYHOST = 0). Use only for developing and testing purposes on NON PRODUCTION environments.'),
      '#default_value' => $translator->getSetting('context_skip_host_verifying'),
      '#required' => FALSE,
    ];

    $form['retrieval_type'] = [
      '#type' => 'select',
      '#title' => t('The desired format for download'),
      '#default_value' => $translator->getSetting('retrieval_type'),
      '#options' => [
        'pending' => t('Smartling returns any translations (including non-published translations)'),
        'published' => t('Smartling returns only published/pre-published translations'),
        'pseudo' => t('Smartling returns a modified version of the original text'),
      ],
      '#required' => FALSE,
    ];

    $form['auto_authorize_locales'] = [
      '#type' => 'checkbox',
      '#title' => t('Automatically authorize content for translation in Smartling'),
      // @todo Add description to display full URL.
      '#default_value' => $translator->getSetting('auto_authorize_locales'),
      '#required' => FALSE,
    ];

    $form['callback_url_use'] = [
      '#type' => 'checkbox',
      '#title' => t('Use Smartling callback: [host]/tmgmt-smartling-callback/[job_id]'),
      // @todo Add description to display full URL.
      '#default_value' => $translator->getSetting('callback_url_use'),
      '#required' => FALSE,
    ];

    $form['callback_url_host'] = [
      '#type' => 'textfield',
      '#title' => t('Override host value'),
      '#description' => t('Leave blank for default value.'),
      '#default_value' => $translator->getSetting('callback_url_host'),
      '#size' => 63,
      '#states' => [
        'visible' => [
          'input[name="settings[callback_url_use]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    // Any visible, writable wrapper can potentially be used for the files
    // directory, including a remote file system that integrates with a CDN.
    $descriptions = \Drupal::service('stream_wrapper_manager')->getDescriptions(StreamWrapperInterface::WRITE_VISIBLE);

    foreach ($descriptions as $scheme => $description) {
      $options[$scheme] = $description;
    }

    if (!empty($options)) {
      $form['scheme'] = [
        '#type' => 'radios',
        '#title' => t('Download method'),
        '#default_value' => $translator->getSetting('scheme'),
        '#options' => $options,
        '#description' => t('Choose the location where exported files should be stored. The usage of a protected location (e.g. private://) is recommended to prevent unauthorized access.'),
      ];
    }

    $form['custom_regexp_placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Custom placeholder (regular expression)'),
      '#description' => t('The content matching this regular expression will not be editable by translators in Smartling.'),
      '#size' => 63,
      '#maxlength' => 80,
      '#default_value' => $translator->getSetting('custom_regexp_placeholder'),
      '#required' => FALSE,
    ];

    // TODO: identical filename task.
    // $form['identical_file_name'] = [
    //   '#type' => 'checkbox',
    //   '#title' => t('Use identical file names for jobs that contain identical content'),
    //   '#description' => t('Generated file will have identical name for jobs that have identical content.'),
    //   '#default_value' => $translator->getSetting('identical_file_name'),
    //   '#required' => FALSE,
    // ];

    $form['enable_smartling_logging'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Smartling remote logging'),
      '#description' => t('If enabled, <b>only connector related logs</b> will be sent to Smartling - the same information stored in the database.'),
      '#default_value' => $translator->getSetting('enable_smartling_logging'),
    ];

    $form['enable_notifications'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Smartling real-time notifications'),
      '#description' => t('If enabled, users with "see smartling messages" permission will see real-time ui notifications of upload/download process status.'),
      '#default_value' => $translator->getSetting('enable_notifications'),
    ];

    $form['async_mode'] = [
      '#type' => 'checkbox',
      '#title' => t('Asynchronous mode'),
      '#description' => t('Content will be submitted immediately to Smartling when asynchronous mode is disabled.'),
      '#default_value' => $translator->getSetting('async_mode'),
      '#required' => FALSE,
    ];

    $basic_auth_defaults = $translator->getSetting('basic_auth');

    $form['enable_basic_auth'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable basic auth for context'),
      '#default_value' => $translator->getSetting('enable_basic_auth'),
    ];

    $form['basic_auth'] = [
      '#type' => 'details',
      '#title' => t('Basic auth'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          'input[name="settings[enable_basic_auth]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['basic_auth']['login'] = [
      '#type' => 'textfield',
      '#title' => t('Login'),
      '#default_value' => $basic_auth_defaults['login'],
      '#states' => [
        'required' => [
          'input[name="settings[enable_basic_auth]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['basic_auth']['password'] = [
      '#type' => 'password',
      '#title' => t('Password'),
      '#attributes' => [
        'value' => $basic_auth_defaults['password'],
      ],
      '#states' => [
        'required' => [
          'input[name="settings[enable_basic_auth]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    // Cron & queues table.
    $form['cron_and_queues'] = [
      '#type' => 'details',
      '#title' => t('Cron & queues'),
      '#open' => TRUE,
      '#tree' => FALSE,
    ];

    $header = [
      'title' => t('Queue'),
      'items' => t('Number of items'),
    ];

    $options = [];

    foreach (SmartlingTranslatorUi::getSmartlingQueuesDefinitions() as $name => $queue_definition) {
      $queue = \Drupal::service('queue')->get($name);
      $title = (string) $queue_definition['title'];
      $options[$name] = [
        'title' => $title,
        'items' => $queue->numberOfItems(),
      ];
    }

    $last_cron_run_time = \Drupal::service('date.formatter')->formatTimeDiffSince(\Drupal::state()->get('system.cron_last'));

    $form['cron_and_queues']['top'] = [
      '#type' => 'container',
      'last_run' => [
        '#markup' => t('Last cron run: %time ago.', ['%time' => $last_cron_run_time]),
      ],
      'actions' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['form-actions'],
        ],
        'run_cron' => [
          '#type' => 'submit',
          '#submit' => [[$this, 'runCron']],
          '#value' => t('Run cron'),
        ],
      ],
    ];

    $form['cron_and_queues']['queues'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => t('No queues defined'),
    ];

    $form['cron_and_queues']['bottom'] = [
      'actions' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['form-actions'],
        ],
        'process_selected_queues' => [
          '#type' => 'submit',
          '#submit' => [[$this, 'processSelectedQueues']],
          '#value' => t('Process selected queues'),
        ],
      ],
    ];

    $form['bucket_name'] = [
      '#markup' => t('Submissions bucket name: <b>@bucket_name</b>', [
        '@bucket_name' => Drupal::state()->get('tmgmt_smartling.bucket_name', 'tmgmt_smartling_default_bucket_name'),
      ]),
    ];

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function processSelectedQueues(array &$form, FormStateInterface $form_state) {
    $selected_queues = array_filter($form_state->getValue('queues'));

    if (!empty($selected_queues)) {
      $batch = [
        'operations' => []
      ];

      // We can't switch user in a middle of the batch process so unset
      // context queue from processing array if there are some additional
      // queues selected.
      if (count($selected_queues) > 1 && in_array('smartling_context_upload', $selected_queues)) {
        unset($selected_queues['smartling_context_upload']);
        drupal_set_message(t('Please, process "Upload context" queue separately.'), 'warning');
      }

      foreach ($selected_queues as $queue_name) {
        $queue = \Drupal::service('queue')->get($queue_name);

        if ($queue->numberOfItems()) {
          // Special case for context uploading queue: we have to switch user
          // before doing this in a batch and switch user back when we finish.
          if ($queue_name == 'smartling_context_upload') {
            $batch['finished'] = [get_class($this), 'finishBatch'];

            $translator_settings = $form_state->getValue('settings');
            \Drupal::getContainer()
              ->get('user.shared_tempstore')
              ->get(self::TEMP_STORAGE_NAME)
              ->set(
                self::USER_NAME_BEFORE_SWITCHING,
                \Drupal::currentUser()->getAccountName()
              );

            try {
              \Drupal::getContainer()
                ->get('tmgmt_smartling.utils.context.user_auth')
                ->switchUser(
                  $translator_settings['contextUsername'],
                  $translator_settings['context_silent_user_switching']
                );
            }
            catch (Exception $e) {
              watchdog_exception('tmgmt_smartling', $e);
            }
          }

          foreach (range(1, $queue->numberOfItems()) as $index) {
            $batch['operations'][] = ['\Drupal\tmgmt_smartling\SmartlingTranslatorUi::step', [$queue_name]];
          }
        }
      }

      batch_set($batch);
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function runCron(array &$form, FormStateInterface $form_state) {
    if (\Drupal::service('cron')->run()) {
      drupal_set_message(t('Cron ran successfully.'));
    }
    else {
      drupal_set_message(t('Cron run failed.'), 'error');
    }
  }

  /**
   * Batch step definition to process one queue item.
   *
   * Based on \Drupal\Core\Cron::processQueues().
   */
  public static function step($queue_name, $context) {
    if (isset($context['interrupted']) && $context['interrupted']) {
      return;
    }

    $queue_manager = \Drupal::service('plugin.manager.queue_worker');
    $queue_factory = \Drupal::service('queue');
    $info = $queue_manager->getDefinition($queue_name);
    $title = $info['title'];

    // Make sure every queue exists. There is no harm in trying to recreate
    // an existing queue.
    $queue_factory->get($queue_name)->createQueue();

    $queue_worker = $queue_manager->createInstance($queue_name);
    $queue = $queue_factory->get($queue_name);

    if ($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);
        $context['message'] = $title;
        $queue->deleteItem($item);
      }
      catch (SuspendQueueException $e) {
        // If the worker indicates there is a problem with the whole queue,
        // release the item and skip to the next queue.
        $queue->releaseItem($item);

        watchdog_exception('cron', $e);

        // Skip to the next queue.
        $context['interrupted'] = TRUE;
      }
      catch (Exception $e) {
        // In case of any other kind of exception, log it and leave the item
        // in the queue to be processed again later.
        watchdog_exception('cron', $e);
      }
    }
  }

  /**
   * Finish batch callback.
   *
   * Switch user back after "Upload context" queue batch processing.
   */
  public static function finishBatch() {
    $user_name_before_switching = \Drupal::getContainer()
      ->get('user.shared_tempstore')
      ->get(self::TEMP_STORAGE_NAME)
      ->get(self::USER_NAME_BEFORE_SWITCHING);

    \Drupal::getContainer()->get('tmgmt_smartling.utils.context.user_auth')->switchUser($user_name_before_switching);
  }

  /**
   * Returns Smartling queues definitions.
   *
   * @return array
   */
  public static function getSmartlingQueuesDefinitions() {
    return array_filter(\Drupal::service('plugin.manager.queue_worker')->getDefinitions(), function($worker) {
      return in_array($worker['id'], [
        'tmgmt_extension_suit_download',
        'tmgmt_extension_suit_upload',
        'smartling_context_upload',
      ]);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    $supported_remote_languages = $translator->getPlugin()->getSupportedRemoteLanguages($translator);

    if (empty($supported_remote_languages)) {
      $form_state->setErrorByName('settings][project_id', t('The "User Id", "Token Secret", or "Project Id" are not correct.'));
      $form_state->setErrorByName('settings][user_id', t('The "User Id", "Token Secret", or "Project Id" are not correct.'));
      $form_state->setErrorByName('settings][token_secret', t('The "User Id", "Token Secret", or "Project Id" are not correct.'));
    }

    if ($translator->getSetting('enable_basic_auth')) {
      $auth_settings = $translator->getSetting('basic_auth');

      if (
        empty($auth_settings['login']) ||
        empty($auth_settings['password'])
      ) {
        $form_state->setErrorByName(
          'settings][basic_auth',
          t('Please fill in both login and password (HTTP basic authentication credentials).')
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutSettingsForm(array $form, FormStateInterface $form_state, JobInterface $job) {
    $plugin_id = $form_state->getValue('translator');
    $translator = $form_state->getFormObject()->getEntity()->getTranslator();
    $add_to_job_values = [];
    $user_input = $form_state->getUserInput();

    // Pass needed values into "Add to job" form only on "Change job"
    // ajax event.
    if (!empty($user_input['_triggering_element_name']) &&
      $user_input['_triggering_element_name'] == 'settings[add_to_job_tab][container][job_id]' &&
      !empty($user_input['settings']['add_to_job_tab'])
    ) {
      $add_to_job_values = $user_input['settings']['add_to_job_tab'];
    }

    if (!empty($plugin_id)) {
      $translator = Translator::load($plugin_id);
    }

    $form['smartling_users_time_zone'] = [
      '#type' => 'hidden',
    ];

    $form['switcher'] = [
      '#type' => 'radios',
      '#default_value' => 0,
      '#options' => [
        t('Create new job'),
        t('Add to job'),
      ],
    ];

    $form['create_new_job_tab'] = [
      '#type' => 'fieldset',
      '#title' => t('Create job'),
      '#states' => [
        'visible' => [
          ':input[name="settings[switcher]"]' => ['value' => 0],
        ],
      ],
    ] + $this->checkoutSettingsCreateJobForm($translator);

    $form['add_to_job_tab'] = [
      '#type' => 'fieldset',
      '#title' => t('Add to job'),
      '#states' => [
        'visible' => [
          ':input[name="settings[switcher]"]' => ['value' => 1],
        ],
      ],
    ] + $this->checkoutSettingsAddToJobForm($translator, [
      JobStatus::AWAITING_AUTHORIZATION,
      JobStatus::IN_PROGRESS,
      JobStatus::COMPLETED,
    ], $add_to_job_values);

    $form['#attached']['library'][] = 'tmgmt_smartling/checkout.settings';

    return parent::checkoutSettingsForm($form, $form_state, $job);
  }

  /**
   * Returns "Create job" form part.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   * @return array
   */
  protected function checkoutSettingsCreateJobForm(TranslatorInterface $translator) {
    return [
      'name' => [
        '#type' => 'textfield',
        '#title' => t('Name'),
        '#states' => [
          'required' => [
            ':input[name="settings[switcher]"]' => ['value' => 0],
          ],
        ],
      ],
      'description' => [
        '#type' => 'textarea',
        '#title' => t('Description'),
      ],
      'due_date' => [
        '#type' => 'datetime',
        '#date_year_range' => date('Y') . ':+5',
        '#default_value' => NULL,
        '#title' => t('Due date'),
        '#date_increment' => 60,
      ],
      'authorize' => [
        '#type' => 'checkbox',
        '#title' => t('Authorize'),
        '#default_value' => $translator->getSetting('auto_authorize_locales'),
      ],
    ];
  }

  /**
   * Returns "Add to job" from part.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   * @param array $statuses
   * @param array $add_to_job_values
   * @return array
   */
  protected function checkoutSettingsAddToJobForm(TranslatorInterface $translator, array $statuses, array $add_to_job_values) {
    $available_jobs = $translator->getPlugin()->getApiWrapper($translator->getSettings())->listJobs(NULL, $statuses);

    if (empty($available_jobs['items'])) {
      $form = [
        'job_info' => [
          '#type' => 'fieldset',
          '#title' => 'info',
          'message' => [
            '#markup' => t('There are no available jobs'),
          ],
        ],
      ];
    }
    else {
      $options = [];
      $files = [];
      $project_id = $translator->getSetting('project_id');

      foreach ($available_jobs['items'] as $item) {
        $options[$item['translationJobUid']] = $item['jobName'];
      }

      // Default values by first page load.
      if (empty($add_to_job_values)) {
        $selected_job_id = $available_jobs['items'][0]['translationJobUid'];
      }
      else {
        // Get default values from selected job.
        $selected_job_id = $add_to_job_values['container']['job_id'];
      }

      $selected_job = $translator->getPlugin()->getApiWrapper($translator->getSettings())->getJob($selected_job_id);
      $default_description = $selected_job['description'];
      $default_job_state = ucwords(strtolower(str_replace('_', ' ', $selected_job['jobStatus'])));
      $source_files = $selected_job['sourceFiles'];

      foreach ($source_files as $source_file) {
        $file_name = urlencode($source_file['name']);
        $files[] = Link::fromTextAndUrl($source_file['name'],
          Url::fromUri("https://dashboard.smartling.com/projects/{$project_id}/files/files.htm", [
            'fragment' => "file/{$file_name}",
            'attributes' => [
              'target' => '_blank',
            ],
          ]))->toString();
      }

      $files_markup = empty($files) ? [
        '#markup' => t('There are no files inside this job'),
      ] : [
        '#theme' => 'item_list',
        '#items' => $files,
      ];;

      $form = [
        'container' => [
          '#prefix' => '<div id="smartling-job-form-wrapper">',
          '#suffix' => '</div>',
          '#type' => 'container',
          'job_id' => [
            '#type' => 'select',
            '#title' => t('Job'),
            '#options' => $options,
            '#ajax' => [
              'callback' => 'tmgmt_smartling_checkout_settings_add_to_job_form_ajax_callback',
              'wrapper' => 'smartling-job-form-wrapper',
            ],
          ],
          'job_info' => [
            '#type' => 'details',
            '#open' => TRUE,
            '#title' => t('Info'),
            '#collapsible' => TRUE,
            'dashboard_link' => [
              '#type' => 'item',
              '#markup' => Link::fromTextAndUrl($selected_job['jobName'],
                Url::fromUri("https://dashboard.smartling.com/app/projects/{$project_id}/jobs/{$selected_job_id}",
                  [
                    'attributes' => [
                      'target' => '_blank',
                    ],
                  ]))->toString(),
              '#title' => t('Dashboard link'),
            ],
            'state' => [
              '#type' => 'item',
              '#markup' => $default_job_state,
              '#title' => t('State'),
            ],
            'description' => [
              '#type' => 'textarea',
              '#title' => t('Description'),
              '#value' => $default_description,
            ],
            'due_date' => [
              '#type' => 'datetime',
              '#date_year_range' => date('Y') . ':+5',
              '#default_value' => NULL,
              '#title' => t('Due date'),
              '#date_increment' => 60,
            ],
            'utc_due_date_hidden' => [
              '#type' => 'hidden',
              '#value' => $selected_job['dueDate'],
            ],
            'authorize' => [
              '#type' => 'checkbox',
              '#title' => t('Authorize'),
              '#value' => $translator->getSetting('auto_authorize_locales'),
            ],
            'name' => [
              '#type' => 'value',
              '#value' => $selected_job['jobName'],
            ],
            'files_container' => [
              '#type' => 'details',
              '#collapsible' => TRUE,
              '#collapsed' => TRUE,
              '#title' => t('Files'),
              'files' => [
                '#markup' => \Drupal::service('renderer')->render($files_markup),
              ],
            ],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutInfo(JobInterface $job) {
    // If the job is finished, it's not possible to import translations anymore.
    if ($job->isFinished()) {
      return parent::checkoutInfo($job);
    }
    $output = [];

    try {
      $output = array(
        '#type' => 'fieldset',
        '#title' => t('Import translated file'),
      );

      $output['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Download'),
        '#submit' => ['tmgmt_smartling_download_file_submit'],
      );

      $output = $this->checkoutInfoWrapper($job, $output);
    }
    catch (Exception $e) {

    }

    return $output;
  }

}

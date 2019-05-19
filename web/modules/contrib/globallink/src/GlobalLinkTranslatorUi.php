<?php

/**
 * @file
 * Contains Drupal\globallink\GlobalLinkTranslatorUi.
 */

namespace Drupal\globallink;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Render\Element;
use Drupal\globallink\Plugin\tmgmt\Translator\GlobalLinkTranslator;
use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\JobInterface;

/**
 * GlobalLink translator UI.
 */
class GlobalLinkTranslatorUi extends TranslatorPluginUiBase {

  /**
   * {@inheritdoc}
   */
  public function reviewForm(array $form, FormStateInterface $form_state, JobItemInterface $item) {
    /** @var \Drupal\globallink\Plugin\tmgmt\Translator\GlobalLinkTranslator $translator_plugin */
    $translator_plugin = $item->getTranslator()->getPlugin();
    $translator_plugin->setTranslator($item->getTranslator());
    $mappings = $item->getRemoteMappings();
    /** @var \Drupal\tmgmt\Entity\RemoteMapping $mapping */
    $mapping = array_shift($mappings);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();

    $form['pd_url'] = [
      '#type' => 'url',
      '#title' => t('GlobalLink api url'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('pd_url'),
      '#description' => t('Add the api url provided by translations.com'),
    ];
    $form['pd_username'] = [
      '#type' => 'textfield',
      '#title' => t('GlobalLink username'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('pd_username'),
      '#description' => t('Add the username provided by translations.com'),
    ];
    $form['pd_password'] = [
      '#type' => 'password',
      '#title' => t('GlobalLink password'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('pd_password'),
      '#description' => t('Add the password provided by translations.com'),
    ];
    $form['pd_projectid'] = [
      '#type' => 'textfield',
      '#title' => t('GlobalLink project id'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('pd_projectid'),
      '#description' => t('Add the project id provided by translations.com'),
    ];
    $form['pd_submissionprefix'] = [
      '#type' => 'textfield',
      '#title' => t('GlobalLink submission prefix'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('pd_submissionprefix'),
      '#description' => t('Choose a prefix'),
    ];
    $form['pd_classifier'] = [
      '#type' => 'textfield',
      '#title' => t('GlobalLink classifier'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('pd_classifier'),
      '#description' => t('Add a classifier'),
    ];
    $form['pd_notify_emails'] = [
      '#type' => 'textfield',
      '#title' => t('Emails for notification'),
      '#default_value' => $translator->getSetting('pd_notify_emails'),
      '#description' => t('A space separated list of emails to notify. Leave blank for no notifications'),
    ];
    $form['pd_combine'] = [
      '#type' => 'checkbox',
      '#title' => t('Combine all items into a single document'),
      '#default_value' => $translator->getSetting('pd_combine'),
      '#description' => t('If checked, a single document will be sent for a translation job, otherwise a separate document within a single submission will be created for each job item.'),
    ];
    $form['pd_due_date_offset'] = [
      '#type' => 'textfield',
      '#title' => t('Default due date offset in working days'),
      '#default_value' => $translator->getSetting('pd_due_date_offset') ?: 3,
      '#description' => t('Controls the default due date, which then by default uses the configured amount of working days in the future.'),
    ];
    $form['pd_notify_level'] = [
      '#type' => 'checkboxes',
      '#title' => t('Email notification levels'),
      '#options' => [
        GlobalLinkTranslator::MSG_STATUS => t('Status'),
        GlobalLinkTranslator::MSG_DEBUG => t('Debug'),
        GlobalLinkTranslator::MSG_WARNING => t('Warning'),
        GlobalLinkTranslator::MSG_ERROR => t('Error'),
      ],
      '#default_value' => (array) $translator->getSetting('pd_notify_level'),
      '#description' => t('Select which tmgmt message types to send via email. Selecting all can result in a high volume of emails being sent.')
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $settings = $form['plugin_wrapper']['settings'];
    $adapter = \Drupal::getContainer()->get('globallink.gl_exchange_adapter');
    $values = $form_state->getValue('settings');
    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    /** @var \Drupal\tmgmt_oht\Plugin\tmgmt\Translator\OhtTranslator $translator_plugin */
    $translator_plugin = $translator->getPlugin();

    try {
      $pd_config = $adapter->getPDConfig([
        'pd_url' => $values['pd_url'],
        'pd_username' => $values['pd_username'],
        'pd_password' => $values['pd_password'],
      ]);

      // Test connections settings.
      $adapter->getGlExchange($pd_config);

      // Test language mappings.
      $all_supported = [];
      // Flatten the array of supported pairs.
      $supported_pairs = $translator_plugin->getSupportedLanguagePairs($translator);
      foreach ($supported_pairs as $supported_pair) {
        foreach ($supported_pair as $item) {
          $all_supported[$item] = $item;
        }
      }

      $unsupported = [];
      $mappings = $form_state->getValue('remote_languages_mappings');
      foreach ($mappings as $mapping) {
        if (!in_array($mapping, $all_supported)) {
          $unsupported[] = $mapping;
        }
      }

      if ($unsupported) {
        $element = $form['plugin_wrapper']['remote_languages_mappings'];
        foreach (Element::children($element) as $key) {
          if (!empty($element[$key]['#value']) && in_array($element[$key]['#value'], $unsupported)) {
            $form_state->setError($element[$key], t('The following language codes are not supported by this project: %codes', ['%codes' => implode(', ', $unsupported)]));
          }
        }
      }

      // Validate email addresses.
      if (!empty($values['pd_notify_emails'])) {
        $emails = explode(' ', $values['pd_notify_emails']);
        $email_validator = \Drupal::service('email.validator');
        $invalid_emails = [];
        foreach ($emails as $email) {
          trim($email);
          if (!$email_validator->isValid($email)) {
            $invalid_emails[] = $email;
          }
        }

        if ($invalid_emails) {
          $form_state->setError($settings['pd_notify_emails'], t('Invalid email address(es) found: %emails', ['%emails' => implode(' ', $invalid_emails)]));
        }
      }
    }
    catch (\Exception $e) {
      $form_state->setError($settings, t('Login credentials are incorrect.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutSettingsForm(array $form, FormStateInterface $form_state, JobInterface $job) {
    /** @var \Drupal\globallink\Plugin\tmgmt\Translator\GlobalLinkTranslator $translator_plugin */
    $translator_plugin = $job->getTranslator()->getPlugin();
    $translator_plugin->setTranslator($job->getTranslator());

    $form['comment'] = [
      '#type' => 'textarea',
      '#title' => t('Instructions'),
      '#description' => t('You can provide a set of instructions so that the translator will better understand your requirements.'),
      '#default_value' => $job->getSetting('comment') ? $job->getSetting('comment') : '',
    ];

    $offset_days = $job->getTranslator()->getSetting('pd_due_date_offset') ?: 3;
    if ($job->getSetting('due') && isset($job->getSetting('due')['object']) && $job->getSetting('due')['object'] instanceof DrupalDateTime) {
      $default_due = $job->getSetting('due')['object'];
    }
    else {
      $default_due = new DrupalDateTime("+$offset_days weekday");
    }

    if ($job->isContinuous()) {
      $form['required_by'] = [
        '#type' => 'number',
        '#title' => t('Required By (Workdays days)'),
        '#description' => t('Enter the number of working days before the translation is required.'),
        '#default_value' => $job->getSetting('required_by') ? $job->getSetting('required_by') : $offset_days,
        '#min' => 1,
      ];

      $this->buildContinuousFilter($form, $form_state, $job);
    }
    else {
      $form['due'] = [
        '#type' => 'datetime',
        '#date_time_element' => 'none',
        '#title' => t('Due date'),
        '#required' => TRUE,
        '#default_value' => $default_due,
        '#element_validate' => [[get_class($this), 'validateDueDate']],
        '#description' => t('Due will be set using %timezone timezone.', ['%timezone' => drupal_get_user_timezone()]),
      ];

      $form['urgent'] = [
        '#type' => 'checkbox',
        '#title' => t('Urgent'),
        '#default_value' => $job->getSetting('urgent') ? $job->getSetting('urgent') : FALSE,
        '#description' => t('Translation will be treated as high priority and may result in additional fees.'),
      ];
    }

    return $form;
  }

  /**
   * Build the continuous filters.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\tmgmt\JobInterface $job
   *   The job object.
   */
  protected function buildContinuousFilter(array &$form, FormStateInterface $form_state, JobInterface $job) {
    $form['filters_table'] = [
      '#type' => 'details',
      '#title' => t('Exclusion filters'),
      '#open' => TRUE,
      '#description' => t('Use exclusion filters to prevent content from being added to the continuous job. The URL allows to use wildcards, use "/blog/*" to exclude all URLs starting with "/blog/", use "*blog*" to exclude all URLs that have blog anywhere in their URL path'),
      '#prefix' => '<div id="selected-filters">',
      '#suffix' => '</div>',
    ];

    $form['filters_table']['filters'] = [
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => [
        'field' => t('Field'),
        'value' => t('Value'),
      ],
    ];

    // @todo It would probably be better not to save the values without value.
    $filters_table = $job->getSetting('filters_table');
    if (!empty($filters_table['filters'])) {
      foreach ($filters_table['filters'] as $filter) {
        if (!empty($filter['value'])) {
          $filters[] = $filter;
        }
      }
    }
    else {
      $filters = [];
    }

    if (!$form_state->has('filters_rows')) {
      $form_state->set('filters_rows', count($filters) + 1);
    }

    $field_options = [
      'url' => t('URL matches'),
      'id' => t('ID equals'),
    ];

    for ($i = 0; $i < $form_state->get('filters_rows'); $i++) {
      $filter = isset($filters[$i]) ? $filters[$i] : [];

      $filter += [
        'field' => '',
        'value' => '',
      ];

      $form['filters_table']['filters'][$i] = [
        'field' => [
          '#type' => 'select',
          '#default_value' => $filter['field'],
          '#title' => t('Field'),
          '#title_display' => 'invisible',
          '#options' => $field_options,
        ],
        'value' => [
          '#type' => 'textfield',
          '#default_value' => $filter['value'],
          '#title' => t('Value'),
          '#title_display' => 'invisible',
          '#size' => 40,
        ],
      ];
    }

    // Select element for available filters.
    $form['filters_table']['filter_add_button'] = [
      '#type' => 'submit',
      '#value' => t('Add another filter'),
      '#ajax' => [
        'wrapper' => 'selected-filters',
        'callback' => [$this, 'filtersReplace'],
        'method' => 'replace',
      ],
      '#submit' => [[$this, 'addfilterSubmit']],
    ];
  }

  /**
   * Returns the updated 'filters' fieldset for replacement by ajax.
   *
   * @param array $form
   *   The updated form structure array.
   * @param FormStateInterface $form_state
   *   The form state structure.
   *
   * @return array
   *   The updated form component for the selected fields.
   */
  public function filtersReplace(array $form, FormStateInterface $form_state) {
    return $form['translator_wrapper']['settings']['filters_table'];
  }

  /**
   * Adds sensor to entity when 'Add another filter' button is pressed.
   *
   * @param array $form
   *   The form structure array.
   * @param FormStateInterface $form_state
   *   The form state structure.
   */
  public function addfilterSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    $form_state->set('filters_rows', $form_state->get('filters_rows') + 1);

    drupal_set_message(t('Filter added.'), 'status');
  }


  /**
   * Validate that the due date is in the future.
   *
   * @param array $element
   *   The input element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateDueDate(array $element, FormStateInterface &$form_state) {
    $current_date = new DrupalDateTime();
    if (isset($element['#value']['object'])) {
      $due_date = $element['#value']['object'];

      if ($due_date <= $current_date) {
        $form_state->setError($element, t('Due date must be in the future.'));
      }
    }
    else {
      $form_state->setError($element, t('Due date is invalid. Please enter in yyyy-mm-dd format or select from calendar'));

    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutInfo(JobInterface $job) {
    $form = [];

    if ($job->isActive()) {
      $form['actions']['pull'] = [
        '#type' => 'submit',
        '#value' => t('Pull translations'),
        '#submit' => [[$this, 'submitPullTranslations']],
        '#weight' => -10,
      ];
    }

    return $form;
  }

  /**
   * Submit callback to pull translations form GlobalLink.
   */
  public function submitPullTranslations(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = $form_state->getFormObject()->getEntity();
    /** @var \Drupal\globallink\Plugin\tmgmt\Translator\GlobalLinkTranslator $translator_plugin */
    $translator_plugin = $job->getTranslator()->getPlugin();
    $translator_plugin->fetchJobs($job);
    tmgmt_write_request_messages($job);
  }
}

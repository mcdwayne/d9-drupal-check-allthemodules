<?php

namespace Drupal\tmgmt_capita;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Capita translator UI.
 */
class CapitaTranslatorUi extends TranslatorPluginUiBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();

    $form['environment'] = [
      '#type' => 'radios',
      '#title' => $this->t('Environment'),
      '#description' => $this->t('The environment you are connecting to.'),
      '#options' => [
        'staging' => $this->t('Staging'),
        'production' => $this->t('Production'),
      ],
      '#default_value' => $translator->getSetting('environment'),
      '#required' => TRUE,
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('username'),
      '#description' => $this->t('The username of your Capita TI account.'),
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => $translator->getSetting('password'),
      '#description' => $this->t('The password of your Capita TI account. Leave it empty to use your saved password.'),
    ];
    $form['customer_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer name'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('customer_name'),
      '#description' => $this->t('The complete customer name allocated by Capita TI.'),
    ];
    $form['contact_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contact name'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('contact_name'),
      '#description' => $this->t('The complete name, formatted as LastName, FirstName, of a contact from the previously specified customer.'),
    ];
    $form['due_date_offset'] = [
      '#type' => 'number',
      '#min' => 1,
      '#title' => t('Default due date offset in working days'),
      '#default_value' => $translator->getSetting('due_date_offset') ?: 3,
      '#description' => $this->t('Number of working days in the future that are going to be applied as a default due date value.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();

    // In case a user omits password field, try to use the saved value.
    if (empty($translator->getSetting('password')) && !$translator->isNew()) {
      /** @var \Drupal\tmgmt\TranslatorInterface $original_translator */
      $original_translator = \Drupal::entityTypeManager()->getStorage($translator->getEntityTypeId())->load($translator->id());
      if ($saved_password = $original_translator->getSetting('password')) {
        $translator->setSetting('password', $saved_password);
        $form_state->setValue(['settings', 'password'], $saved_password);
      }
    }

    if ($translator->getSetting('environment') && $translator->getSetting('password')) {
      try {
        $translator->getPlugin()->doRequest($translator, 'languages');
      }
      catch (TMGMTException $e) {
        $form_state->setError($form['plugin_wrapper']['settings'], $e->getMessage());
      }
    }
    else {
      $form_state->setError($form['plugin_wrapper']['settings']['password'], $this->t('The password has not been saved and must be entered.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutSettingsForm(array $form, FormStateInterface $form_state, JobInterface $job) {
    $form['note'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Note'),
      '#description' => $this->t('The instructions and comments for your translation request.'),
      '#default_value' => $job->getSetting('note') ? $job->getSetting('note') : '',
    ];

    // Get a number of offset days or fallback to 3 working days.
    $offset_days = $job->getTranslator()->getSetting('due_date_offset') ?: 3;
    if ($job->getSetting('due_date') && isset($job->getSetting('due_date')['object']) && $job->getSetting('due_date')['object'] instanceof DrupalDateTime) {
      $default_due = $job->getSetting('due_date')['object'];
    }
    else {
      $default_due = new DrupalDateTime("+$offset_days weekday");
    }
    $form['due_date'] = [
      '#type' => 'datetime',
      '#date_timezone' => drupal_get_user_timezone(),
      '#title' => $this->t('Due date'),
      '#required' => TRUE,
      '#default_value' => $default_due,
      '#element_validate' => [[get_class($this), 'validateDueDate']],
      '#description' => $this->t('Due will be set using %timezone timezone.', ['%timezone' => drupal_get_user_timezone()]),
    ];

    return $form;
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
      /** @var \Drupal\Core\Datetime\DrupalDateTime $due_date */
      $due_date = $element['#value']['object'];

      if ($due_date <= $current_date) {
        $form_state->setError($element, t('Due date must be in the future.'));
      }
      elseif (!$form_state->isRebuilding()) {
        $form_state->setValue(['settings', 'due_date'], $due_date->format('Y-m-d H:i:s'));
      }
    }
    else {
      $form_state->setError($element, t('Due date is invalid. Please enter in mm/dd/yyyy format or select from calendar'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutInfo(JobInterface $job) {
    $form = [];
    $request_id = $job->getReference();
    $form['info'][] = [
      '#type' => 'item',
      '#title' => $this->t('Capita TI request ID'),
      '#markup' => $request_id,
    ];
    /** @var \Drupal\tmgmt_capita\Plugin\tmgmt\Translator\CapitaTranslator $translator_plugin */
    $translator_plugin = $job->getTranslatorPlugin();
    if ($job->isActive()) {
      try {
        $request_data = $translator_plugin->doRequest($job->getTranslator(), "requests/$request_id");
        if ($translator_plugin->isTranslationCompleted($request_data)) {
          $form['actions']['pull'] = [
            '#type' => 'submit',
            '#value' => $this->t('Pull translations'),
            '#submit' => [[$this, 'submitPullTranslations']],
          ];
        }
        else {
          $form['info'][] = [
            '#type' => 'item',
            '#title' => $this->t('Current status'),
            '#markup' => $request_data['RequestStatus'],
          ];
          $form['info'][] = [
            '#type' => 'item',
            '#title' => $this->t('Expected delivery date'),
            '#markup' => $request_data['DeliveryDate'],
          ];
        }
      }
      catch (TMGMTException $e) {
      }
    }

    return $form;
  }

  /**
   * Submit callback to pull translations from Capita TI.
   */
  public function submitPullTranslations(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = $form_state->getFormObject()->getEntity();
    /** @var \Drupal\tmgmt_capita\Plugin\tmgmt\Translator\CapitaTranslator $translator_plugin */
    $translator_plugin = $job->getTranslator()->getPlugin();
    $translator_plugin->fetchTranslations($job);
    tmgmt_write_request_messages($job);
  }

}

<?php

namespace Drupal\webform_scheduled_tasks\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTask;

/**
 * The scheduled task form.
 */
class WebformScheduledTaskForm extends EntityForm {

  /**
   * The associated webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $webform = NULL) {
    $this->webform = $webform;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface $schedule */
    $schedule = $this->entity;

    if ($schedule->isNew()) {
      $form['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Task name'),
        '#maxlength' => 255,
        '#default_value' => $schedule->label(),
        '#required' => TRUE,
      ];
      $form['id'] = [
        '#type' => 'machine_name',
        '#default_value' => $schedule->id(),
        '#machine_name' => [
          'exists' => [WebformScheduledTask::class, 'load'],
        ],
      ];
      $form['task_type'] = [
        '#title' => $this->t('Task to run'),
        '#type' => 'select',
        '#required' => TRUE,
        '#description' => $this->t('Select the task which should be run when this scheduled task is executed.'),
        '#options' => $this->pluginManagerOptionsList('plugin.manager.webform_scheduled_tasks.task'),
      ];
      $form['result_set_type'] = [
        '#title' => $this->t('Submissions to process'),
        '#type' => 'select',
        '#required' => TRUE,
        '#description' => $this->t('Select the results which should be used for .'),
        '#options' => $this->pluginManagerOptionsList('plugin.manager.webform_scheduled_tasks.result_set'),
      ];
      $form['webform'] = [
        '#type' => 'value',
        '#value' => $this->webform->id(),
      ];
    }
    else {

      $form['scheduled_task_info'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Scheduled task information'),
        'children' => [
          'task' => [
            '#type' => 'container',
            '#markup' => $this->t('<strong>Task type:</strong> @type', ['@type' => $schedule->getTaskPlugin()->label()]),
          ],
          'result_set' => [
            '#type' => 'container',
            '#markup' => $this->t('<strong>Result set type:</strong> @type', ['@type' => $schedule->getResultSetPlugin()->label()]),
          ],
          'status' => [
            '#type' => 'container',
            '#markup' => $this->t('<strong>Status:</strong> @status', ['@status' => $schedule->isHalted() ? $this->t('Halted') : $this->t('Active')]),
          ],
        ],
      ];

      if ($schedule->isHalted()) {
        $form['scheduled_task_info']['children']['halted_info'] = [
          'message' => [
            '#type' => 'container',
            '#markup' => $this->t('<strong>Halted with message:</strong> @message', ['@message' => $schedule->getHaltedReason()]),
          ],
          'resume' => [
            '#type' => 'submit',
            '#value' => $this->t('Resume task'),
            '#description' => $this->t('If the reason this task was halted has been resolved and operations can resume safely, this task may be resumed.'),
            '#submit' => ['::submitForm', '::resume'],
          ],
        ];
      }

      $form['schedule_settings'] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#tree' => FALSE,
        '#title' => $this->t('Schedule'),
        'children' => [],
      ];
      $form['schedule_settings']['next_run'] = [
        '#title' => $this->t('Next scheduled run'),
        '#type' => 'datetime',
        '#description' => $this->t('You may use this field to manually adjust the next time the task will run. If left blank this calculated from the current time.'),
        '#default_value' => $schedule->getNextTaskRunDate() ? DrupalDateTime::createFromTimestamp($schedule->getNextTaskRunDate()) : NULL,
      ];
      $form['schedule_settings']['interval'] = [
        '#type' => 'container',
        '#tree' => TRUE,
      ];
      $form['schedule_settings']['interval']['amount'] = [
        '#title' => $this->t('Interval'),
        '#type' => 'number',
        '#field_prefix' => $this->t('Run this scheduled task every') . ' ',
        '#required' => TRUE,
        '#min' => 1,
        '#wrapper_attributes' => [
          'style' => 'display: inline-block;',
        ],
        '#default_value' => $schedule->getRunIntervalAmount(),
      ];
      $form['schedule_settings']['interval']['multiplier'] = [
        '#type' => 'select',
        '#required' => TRUE,
        '#wrapper_attributes' => [
          'style' => 'display: inline-block;',
        ],
        '#options' => [
          60 => $this->t('Minutes'),
          60 * 60 => $this->t('Hours'),
          60 * 60 * 24 => $this->t('Days'),
          60 * 60 * 24 * 7 => $this->t('Weeks'),
        ],
        '#default_value' => $schedule->getRunIntervalMultiplier(),
      ];

      $form['task_settings'] = $this->createPluginForm('task_settings', $this->t('Task settings'), $schedule->getTaskPlugin(), $form, $form_state);
      $form['result_set_settings'] = $this->createPluginForm('result_set_settings', $this->t('Result set settings'), $schedule->getResultSetPlugin(), $form, $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface $schedule */
    $schedule = $this->entity;
    if (!$schedule->isNew()) {
      $this->validatePluginForm($form['task_settings'], $schedule->getTaskPlugin(), $form, $form_state);
      $this->validatePluginForm($form['result_set_settings'], $schedule->getResultSetPlugin(), $form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface $schedule */
    $schedule = $this->entity;
    if (!$schedule->isNew()) {
      $this->submitPluginForm($form['task_settings'], $schedule->getTaskPlugin(), $form, $form_state);
      $this->submitPluginForm($form['result_set_settings'], $schedule->getResultSetPlugin(), $form, $form_state);
    }

    // The next time the task runs isn't a property on the config entity, so it
    // must be set manually.
    if ($next_run = $form_state->getValue('next_run')) {
      $schedule->setNextTaskRunDate($next_run->getTimestamp());
    }

    // On the first save, when the type plugins are set, redirect to the edit
    // form to complete adding the settings for those plugin types.
    $redirect_collection = $this->entity->isNew() ? 'edit-form' : 'collection';

    parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('The scheduled task was saved successfully.'));
    $form_state->setRedirect($this->entity->toUrl($redirect_collection)->getRouteName(), $this->entity->toUrl($redirect_collection)->getRouteParameters());
  }

  /**
   * {@inheritdoc}
   */
  public function resume(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface $schedule */
    $schedule = $this->entity;

    // Resume the task, but queue it up to run from the current time to the next
    // interval.
    $this->messenger()->addStatus($this->t('The scheduled task was resumed and will run during the next scheduled interval.'));
    $schedule->incrementTaskRunDateByInterval();
    $schedule->resume();
  }

  /**
   * Create a plugin sub-form.
   *
   * @param string $key
   *   The key that will be used to store this plugin form.
   * @param string $label
   *   The label of the section.
   * @param \Drupal\Core\Plugin\PluginFormInterface $plugin
   *   The plugin to add a form for.
   * @param array $parent_form
   *   The parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The parent form state.
   *
   * @return array
   *   A sub-form.
   */
  protected function createPluginForm($key, $label, PluginFormInterface $plugin, array $parent_form, FormStateInterface $form_state) {
    $form = [
      '#parents' => [$key],
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#title' => $label,
    ];
    $parent_form['#parents'] = [];
    $sub_form_state = SubformState::createForSubform($form, $parent_form, $form_state);
    $form += $plugin->buildConfigurationForm($form, $sub_form_state);
    return $form;
  }

  /**
   * Validate a plugin form.
   *
   * @param array $plugin_form
   *   The plugin form.
   * @param \Drupal\Core\Plugin\PluginFormInterface $plugin
   *   The plugin.
   * @param array $parent_form
   *   The parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function validatePluginForm(array $plugin_form, PluginFormInterface $plugin, array $parent_form, FormStateInterface $form_state) {
    $sub_form_state = SubformState::createForSubform($plugin_form, $parent_form, $form_state);
    $plugin->validateConfigurationForm($plugin_form, $sub_form_state);
  }

  /**
   * Submit a plugin form.
   */
  protected function submitPluginForm($plugin_form, PluginFormInterface $plugin, $parent_form, $form_state) {
    $sub_form_state = SubformState::createForSubform($plugin_form, $parent_form, $form_state);
    $plugin->submitConfigurationForm($plugin_form, $sub_form_state);
  }

  /**
   * Get an options list from a plugin manager.
   *
   * @param string $manager
   *   The ID of the plugin manager to generate an options list for.
   *
   * @return array
   *   An array for options for a select list.
   */
  protected function pluginManagerOptionsList($manager) {
    /** @var \Drupal\Core\Plugin\DefaultPluginManager $manager */
    $manager = \Drupal::service($manager);
    return array_column($manager->getDefinitions(), 'label', 'id');
  }

}

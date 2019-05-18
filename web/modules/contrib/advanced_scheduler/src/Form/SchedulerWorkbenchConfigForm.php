<?php

namespace Drupal\advanced_scheduler\Form;

use Drupal\advanced_scheduler\Controller\SchedulerModeration;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Define a form that configures scheduler states.
 */
class SchedulerWorkbenchConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'advanced_scheduler.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advanced_scheduler_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $moderation_states = SchedulerModeration::getAllWorkbenchModerationStates();

    $state_transition = SchedulerModeration::getScheduledConfig();
    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Scheduled states configuration'),
    ];
    if (!empty($moderation_states)) {
      $form['settings']['state_transition'] = [
        '#type' => 'checkboxes',
        '#description' => $this->t('Contents having above checked moderation states will be published on scheduled date during cron run.'),
        '#options' => $moderation_states,
        '#default_value' => !empty($state_transition) ? $state_transition : [],
      ];
    }
    else {
      drupal_set_message($this->t('Please create moderation states from workbench so that you can select state and contents having below checked states could be published on scheduled date during cron run.'), 'warning');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('state_transition'))) {
      $states_key = [];
      $all_state_transition = $form_state->getValue('state_transition');
      $states_key = SchedulerModeration::getConfigTransitionState($all_state_transition);
      parent::submitForm($form, $form_state);
      // Save scheduled states in config.
      SchedulerModeration::saveScheduledStates($states_key);
    }
  }

}
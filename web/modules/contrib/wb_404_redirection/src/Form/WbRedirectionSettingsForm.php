<?php

namespace Drupal\wb_404_redirection\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workbench_moderation\Entity\ModerationState;

/**
 * {@inheritdoc}
 */
class WbRedirectionSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wb_404_redirection_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['wb_404_redirection.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    //get all moderations states for options
    $moderation_states = $this->getAllWorkbenchModerationStates();

    //get all saved states for this configuration
    $config = $this->config('wb_404_redirection.settings');
    $saved_transitions = $config->get('state_transition');

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Check Workbench moderations states for 404 redirection'),
    ];
    if (!empty($moderation_states)) {
      $form['settings']['state_transition'] = [
        '#type' => 'checkboxes',
        '#description' => $this->t('Checked moderation states will be set to be redirected to 404 i.e. Page not found.'),
        '#options' => $moderation_states,
        '#default_value' => !empty($saved_transitions) ? $saved_transitions : [],
      ];
    }
    else {
      drupal_set_message($this->t('Please create moderation states from workbench so that you can select moderation states to create 404 redirection..'), 'warning');
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('wb_404_redirection.settings')->set('state_transition', $form_state->getValue('state_transition'))->save();
    parent::submitForm($form, $form_state);
  }

   /**
   * Get all workbench moderation states.
   *
   * @method getAllWorkbenchModerationStates
   */
  public function getAllWorkbenchModerationStates() {
    $moderationStates = ModerationState::loadMultiple();
    foreach ($moderationStates as $moderation_state) {
        $options[$moderation_state->id()] = $moderation_state->label();
    }
    return $options;
  }

}
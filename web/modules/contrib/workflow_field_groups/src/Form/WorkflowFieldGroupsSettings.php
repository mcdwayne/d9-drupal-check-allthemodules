<?php

namespace Drupal\workflow_field_groups\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure settings for the workflow_field_groups module.
 */
class WorkflowFieldGroupsSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_field_groups_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['workflow_field_groups.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('workflow_field_groups.settings');

    $form['disabled_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Disabled class'),
      '#description' => $this->t("Class to apply to elements when disabled. On submit, field groups that are visible but not editable are enabled so that the fields within aren't set to empty."),
      '#default_value' => $config->get('disabled_class', 'is-disabled'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('workflow_field_groups.settings')
      ->set('disabled_class', $values['disabled_class'])
      ->save();

    drupal_set_message($this->t('Settings saved.'));
  }

}

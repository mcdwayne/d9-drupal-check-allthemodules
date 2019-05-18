<?php

namespace Drupal\node_accessibility\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures node_accessibility settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_accessibility_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'node_accessibility.settings',
    ];
  }

 /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('node_accessibility.settings');

    $form['alter_revision_menu'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Alter Node Revision View Page'),
      '#description' => $this->t('Designate whether or not the node revision view page should be altered to present node accessibility validation results. Cache must be rebuilt after changing this.'),
      '#default_value' => $config->get('alter_revision_menu'),
    );

    $form['revision_log_counting'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Revision Log Problem Counting'),
      '#description' => $this->t('Designate whether or not to perform problem counting in revision log. This is convenient but may be expensive for very large tables.'),
      '#default_value' => $config->get('revision_log_counting'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('node_accessibility.settings')
      ->set('alter_revision_menu', $values['alter_revision_menu'])
      ->set('revision_log_counting', $values['revision_log_counting'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}

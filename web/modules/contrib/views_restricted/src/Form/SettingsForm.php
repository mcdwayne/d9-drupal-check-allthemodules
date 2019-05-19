<?php

namespace Drupal\views_restricted\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views_restricted_simple\ViewsRestrictedSimple;

/**
 * Configure Views Restricted Simple settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_restricted_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['views_restricted.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug'),
      '#default_value' => $this->config('views_restricted.settings')->get('debug'),
    ];
    $form['backtrace_on_query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Backtrace on query'),
      '#default_value' => $this->config('views_restricted.settings')->get('backtrace_on_query'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('views_restricted.settings')
      ->set('debug', $form_state->getValue('debug'))
      ->set('backtrace_on_query', $form_state->getValue('backtrace_on_query'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}

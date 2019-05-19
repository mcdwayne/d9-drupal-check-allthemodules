<?php

namespace Drupal\views_restricted_simple\Form;

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
    return 'views_restricted_simple_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['views_restricted_simple.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['patterns'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Patterns'),
      '#description' => t('Add one or more (left-anchored) preg patterns like: @pattern', ['@pattern' => '$baseTable/$viewId/$display_id/$type/$table/$field/$alias/']),
      '#default_value' => $this->config('views_restricted_simple.settings')->get('patterns'),
      '#rows' => 20,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $errors = ViewsRestrictedSimple::validatePatternString($form_state->getValue('patterns'));
    foreach ($errors as $error) {
      $form_state->setErrorByName('patterns', $error);
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('views_restricted_simple.settings')
      ->set('patterns', $form_state->getValue('patterns'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}

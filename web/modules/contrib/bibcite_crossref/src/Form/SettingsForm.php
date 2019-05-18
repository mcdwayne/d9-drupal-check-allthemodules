<?php

namespace Drupal\bibcite_crossref\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Crossref configuration.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bibcite_crossref_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bibcite_crossref.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bibcite_crossref.settings');
    $pid = $config->get('bibcite_crossref_mailto');

    $form['bibcite_crossref_mailto'] = [
      '#type' => 'email',
      '#title' => t('Contact email'),
      '#default_value' => $pid ? $pid : '',
      '#description' => t('Your contact information passed with API queries. If provided, API queries will be directed to a special pool of API machines that are reserved for polite users. This way you can be contacted if Crossref sees a problem.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('bibcite_crossref.settings');
    $config->set('bibcite_crossref_mailto', $form_state->getValue('bibcite_crossref_mailto'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}

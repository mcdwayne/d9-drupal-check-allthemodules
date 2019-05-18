<?php

namespace Drupal\bibcite_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Module settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bibcite_import.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bibcite_import_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bibcite_import.settings');

    $form['contributor_deduplication'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Contributor deduplication'),
      '#default_value' => $config->get('settings.contributor_deduplication'),
    ];
    $form['keyword_deduplication'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Keyword deduplication'),
      '#default_value' => $config->get('settings.keyword_deduplication'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('bibcite_import.settings');
    $config->set('settings.contributor_deduplication', $form_state->getValue('contributor_deduplication'));
    $config->set('settings.keyword_deduplication', $form_state->getValue('keyword_deduplication'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}

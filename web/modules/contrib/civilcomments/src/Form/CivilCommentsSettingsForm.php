<?php

namespace Drupal\civilcomments\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;

/**
 * Civil Comments configuration settings form.
 */
class CivilCommentsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civilcomments_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['civilcomments.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Add form elements to collect site account information.
    $form['account'] = [
      '#type' => 'details',
      '#title' => $this->t('Default account settings'),
      '#description' => $this->t('You will need an active subscription to <a href=":url">Civil Comments</a>, and the Site ID associated with your subscription.', [':url' => 'https://www.civilcomments.com/']),
      '#open' => TRUE,
    ];

    $form['account']['civilcomments_site_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site ID'),
      '#default_value' => \Drupal::config('civilcomments.settings')->get('civilcomments_site_id'),
    ];

    $standard_languages = LanguageManager::getStandardLanguageList();
    // Build a select list with language names in native language for the user
    // to choose from. And build a list of available languages for the browser
    // to select the language default from.
    // Select lists based on all standard languages.
    foreach ($standard_languages as $langcode => $language_names) {
      $select_options[$langcode] = $language_names[1];
    }
    $form['account']['civilcomments_lang'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Language'),
      '#default_value' => \Drupal::config('civilcomments.settings')->get('civilcomments_lang'),
      '#options' => $select_options,
    ];

    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('civilcomments.settings')
      ->set('civilcomments_site_id', $form_state->getValue('civilcomments_site_id'))
      ->set('civilcomments_lang', $form_state->getValue('civilcomments_lang'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

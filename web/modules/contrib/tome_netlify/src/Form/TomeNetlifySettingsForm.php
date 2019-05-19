<?php

namespace Drupal\tome_netlify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures Tome Netlify settings for his site.
 */
class TomeNetlifySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tome_netlify_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tome_netlify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('tome_netlify.settings');

    $form['access_token'] = [
      '#type' => 'password',
      '#title' => $this->t('Access token'),
      '#description' => $this->t('A personal access token, which can be generated <a href=":link">by clicking here.</a>', [
        ':link' => 'https://app.netlify.com/account/applications/personal',
      ]),
      '#placeholder' => !empty($config->get('access_token')) ? $this->t('A token has been provided but is not displayed for security reasons.') : '',
    ];

    $form['site_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site ID'),
      '#description' => $this->t('The Netlify site ID to deploy builds to, which can be found in its settings as "API ID". To create a new site, generate and download a static build then upload it on Netlify.'),
      '#default_value' => $config->get('site_id'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('tome_netlify.settings');
    if (!empty($form_state->getValue('access_token'))) {
      $config->set('access_token', $form_state->getValue('access_token'));
    }
    $config->set('site_id', $form_state->getValue('site_id'));
    $config->save();
  }

}

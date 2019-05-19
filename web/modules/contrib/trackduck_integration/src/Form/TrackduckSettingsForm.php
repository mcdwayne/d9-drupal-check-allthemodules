<?php

namespace Drupal\trackduck_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Trackduck settings for this site.
 */
class TrackduckSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'trackduck_trackduck_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['trackduck_integration.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Trackduck'),
      '#default_value' => $this->config('trackduck_integration.settings')->get('enable'),
      '#description' => $this->t('Enable this during development.')
    ];

    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $this->config('trackduck_integration.settings')->get('key'),
      '#description' => $this->t('You can setup your project <a href="@link">here</a>', ['@link' => 'https://trackduck.com/']),
      '#required' => TRUE
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('trackduck_integration.settings')
      ->set('key', $form_state->getValue('key'))
      ->set('enable', $form_state->getValue('enable'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}

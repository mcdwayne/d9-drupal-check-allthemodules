<?php

/**
 * @file
 * Contains \Drupal\browser_refresh\Form\BrowserRefreshSettingsForm.
 */

namespace Drupal\browser_refresh\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure browser-refresh settings for this site.
 */
class BrowserRefreshSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'browser_refresh_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['browser_refresh.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('browser_refresh.settings');

    $form = array();

    $form['enable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable'),
      '#default_value' => $config->get('enable'),
    );

    $form['indicator_location'] = array(
      '#type' => 'select',
      '#title' => t('Location of the indicator'),
      '#default_value' => $config->get('indicator_location'),
      '#options' => array(
        'br' => t('Bottom right'),
        'bl' => t('Bottom left'),
        'tr' => t('Top right'),
        'tl' => t('Top left'),
      ),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->configFactory()->getEditable('browser_refresh.settings');

    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}

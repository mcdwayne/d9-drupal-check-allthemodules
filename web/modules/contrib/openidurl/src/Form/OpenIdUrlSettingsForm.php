<?php

namespace Drupal\openidurl\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure OpenId Url settings.
 */
class OpenIdUrlSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openidurl_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['openidurl.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openidurl.settings');

    $form['version'] = [
      '#type' => 'checkboxes',
      '#required' => TRUE,
      '#title' => $this->t('Version Compatibility'),
      '#default_value' => $config->get('version'),
      '#description' => $this->t("Which versions of OpenID compatibility you would like to support."),
      '#options' => [
        11 => $this->t('1.1'),
        20 => $this->t('2.0'),
      ],
    ];

    $form['server'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenID Server'),
      '#default_value' => $config->get('server'),
      '#description' => $this->t("This is the OpenID server (e.g. http://www.myopenid.com/server)."),
    ];

    $form['delegate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenID Delegate'),
      '#default_value' => $config->get('delegate'),
      '#description' => $this->t("This is the OpenID delegate (e.g. http://yourname.myopenid.com/)."),
    ];

    $form['xrds'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenID XRDS Location'),
      '#default_value' => $config->get('xrds'),
      '#description' => $this->t("This is the OpenID XRDS location (e.g. http://yourname.myopenid.com/xrds)."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('openidurl.settings');

    $config->set('version', array_filter($form_state->getValue('version')));
    $config->set('server', $form_state->getValue('server'));
    $config->set('delegate', $form_state->getValue('delegate'));
    $config->set('xrds', $form_state->getValue('xrds'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}

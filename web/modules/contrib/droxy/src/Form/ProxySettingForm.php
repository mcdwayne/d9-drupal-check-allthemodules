<?php

namespace Drupal\droxy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ProxySettingForm.
 */
class ProxySettingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'droxy.proxysetting',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'proxy_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('droxy.proxysetting');
    $form['proxy_server'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Proxy Server'),
      '#description' => $this->t('The IP address or hostname of the proxy server.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('proxy_server'),
    ];
    $form['proxy_port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Port'),
      '#description' => $this->t('The port number used by the proxy server for client connections.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('proxy_port'),
    ];
    $form['proxy_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Proxy Username'),
      '#description' => $this->t('A username used of proxy server for Authentication.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('proxy_username'),
    ];
    $form['proxy_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Proxy Password'),
      '#description' => $this->t('Password of proxy server used for Authentication.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('proxy_password'),
    ];
    $form['proxy_user_agent'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Proxy User Agent'),
      '#description' => $this->t('A string which can be used by the proxy server to identify connection requests.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('proxy_user_agent'),
    ];
    $form['proxy_exceptions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Proxy Exceptions'),
      '#description' => $this->t('Specify exceptions using either IP addresses or hostnames. Enter one exception per line. Exceptions will be accessed directly, not via proxy.'),
      '#default_value' => $config->get('proxy_exceptions'),
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
    parent::submitForm($form, $form_state);

    $this->config('droxy.proxysetting')
      ->set('proxy_server', $form_state->getValue('proxy_server'))
      ->set('proxy_port', $form_state->getValue('proxy_port'))
      ->set('proxy_username', $form_state->getValue('proxy_username'))
      ->set('proxy_password', $form_state->getValue('proxy_password'))
      ->set('proxy_user_agent', $form_state->getValue('proxy_user_agent'))
      ->set('proxy_exceptions', $form_state->getValue('proxy_exceptions'))
      ->save();
  }

}

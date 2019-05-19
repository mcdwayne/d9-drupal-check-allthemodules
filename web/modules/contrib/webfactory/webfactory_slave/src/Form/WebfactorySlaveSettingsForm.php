<?php

namespace Drupal\webfactory_slave\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures webfactory settings for this site.
 */
class WebfactorySlaveSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webfactory_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('webfactory_slave.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webfactory_slave.settings');

    $form['id'] = array(
      '#type' => 'textfield',
      '#title' => t('Slave ID'),
      '#default_value' => $config->get('id'),
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => TRUE,
    );

    $form['master_ip'] = array(
      '#type' => 'textfield',
      '#title' => t('IP Master'),
      '#default_value' => $config->get('master_ip'),
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => TRUE,
    );
    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => t('Encrypted username to login on Master'),
      '#default_value' => $config->get('authentificate.username'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    );
    $form['password_confirm'] = array(
      '#type' => 'password_confirm',
      '#title' => t('Encrypted password to login on Master'),
      '#size' => 60,
      '#required' => TRUE,
    );
    $form['uniqId'] = array(
      '#type' => 'textfield',
      '#title' => t('Slave authentification uniqId'),
      '#default_value' => $config->get('authentificate.uniqId'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('webfactory_slave.settings');

    $config->set('id', $form_state->getValue('id'));
    $config->set('master_ip', $form_state->getValue('master_ip'));
    $config->set('authentificate.username', $form_state->getValue('username'));
    $config->set('authentificate.password', $form_state->getValue('password_confirm'));
    $config->set('authentificate.uniqId', $form_state->getValue('uniqId'));

    $config->save();
    parent::submitForm($form, $form_state);
  }

}

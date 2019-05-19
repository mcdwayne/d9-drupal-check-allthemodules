<?php

namespace Drupal\simple_openid_server\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates settings form for OpenId.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_openid_server_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('simple_openid_server.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_openid_server.settings');

    $form['client_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Client Id'),
      '#default_value' => $config->get('client_id'),
      '#maxlength' => 255,
      '#description' => t('Unique client id used to authenticate against the server.')
    );
    $form['client_secret'] = array(
      '#type' => 'textfield',
      '#title' => t('Client Secret'),
      '#default_value' => $config->get('client_secret'),
      '#maxlength' => 255,
      '#description' => t('Unique client secret used to authenticate against the server.')
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('simple_openid_server.settings')
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}

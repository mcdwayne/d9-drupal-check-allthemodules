<?php

namespace Drupal\urban_airship_web_push_notifications\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Airship Web Notifications settings.
 */
class CredentialsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['urban_airship_web_push_notifications.configuration'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uawn_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('urban_airship_web_push_notifications.configuration');
    $form['info'] = [
      '#markup' => $this->t('<p>To use this module, you’ll need an account from Airship. <a href="https://www.urbanairship.com/products/web-push-notifications/pricing" target="_blank">Sign up for a free</a> starter plan with unlimited web notifications and up to 1,000 addressable users.</p>
        <p>Credentials can be found in your Airship dashboard. <a href="https://go.urbanairship.com/accounts/login/" target="_blank">Visit your Airship dashboard</a>.</p>'),
    ];
    $form['app_key'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('App Key'),
      '#description'   => $this->t('Airship generated string identifying the app setup. Used in the application bundle.'),
      '#default_value' => $config->get('app_key'),
      '#required'      => TRUE,
    ];
    $form['app_master_secret'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('App Master Secret'),
      '#description'   => $this->t('Airship generated string used for server to server API access. This should never be shared or placed in an application bundle.'),
      '#default_value' => $config->get('app_master_secret'),
      '#required'      => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('urban_airship_web_push_notifications.configuration');
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}

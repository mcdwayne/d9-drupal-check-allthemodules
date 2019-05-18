<?php

namespace Drupal\janrain_connect_social\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JanrainRest\JanrainRest as Janrain;

/**
 * Form for configure messages.
 */
class JanrainConnectSocialForm extends ConfigFormBase {

  /**
   * LoggerChannelFactoryInterface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->loggerFactory = $logger_factory->get('janrain_connect_social');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'janrain_connect_social';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'janrain_connect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('janrain_connect.settings');

    $janrainApi = new Janrain(
      $config->get('capture_server_url'),
      $config->get('config_server'),
      $config->get('flowjs_url'),
      '',
      '',
      $config->get('client_id'),
      $config->get('client_secret'),
      $config->get('application_id'),
      $config->get('default_language'),
      $config->get('flow_name'),
      $this->loggerFactory
    );

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable social login block'),
      '#description' => $this->t('Social login block will appear above the sign-in form.'),
      '#default_value' => $config->get('enable_social_login'),
    ];

    $form['social_providers'] = [
      '#type' => 'details',
      '#title' => $this->t('Social Providers'),
      '#description' => $this->t('Configured social media providers:'),
      '#open' => TRUE,
    ];

    $providers = $janrainApi->providers();

    foreach ($providers['providers'] as $provider) {
      // Not translating social medial names.
      $form['social_providers'][$provider] = [
        '#type' => 'item',
        '#title' => Unicode::ucfirst($provider),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('janrain_connect.settings');

    $config->set('enable_social_login', $form_state->getValue('enabled'))
      ->save();

    // Clear all caches to make social login block available.
    drupal_flush_all_caches();
  }

}

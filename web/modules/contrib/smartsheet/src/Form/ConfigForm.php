<?php

namespace Drupal\smartsheet\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\smartsheet\SmartsheetClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Smartsheet configuration form.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * The Smartsheet client.
   *
   * @var \Drupal\smartsheet\SmartsheetClientInterface
   */
  protected $smartsheetClient;

  /**
   * Constructs a new ConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\smartsheet\SmartsheetClientInterface $smartsheet_client
   *   The Smartsheet client.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SmartsheetClientInterface $smartsheet_client) {
    parent::__construct($config_factory);

    $this->smartsheetClient = $smartsheet_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('smartsheet.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['smartsheet.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smartsheet.config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('smartsheet.config');

    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Smartsheet access token'),
      '#required' => TRUE,
      '#description' => $this->t('You can obtain a Smartsheet access token @here.', [
        '@here' => Link::fromTextAndUrl($this->t('here'), Url::fromUri('https://app.smartsheet.com'))->toString(),
      ]),
      '#default_value' => $config->get('access_token'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // TODO: check if token is valid.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('smartsheet.config')
      ->set('access_token', $form_state->getValue('access_token'))
      ->save();
  }

}

<?php

namespace Drupal\xero\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\xero\XeroQuery;

/**
 * Provide configuration form for user to provide Xero API information for a
 * private application.
 */
class SettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * Inject dependencies into the form except for XeroClient because we want to
   * handle errors properly instead of failing and exploding spectacularly.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration factory interface.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger channel factory for logging.
   * @param \Drupal\xero\XeroQuery $query
   *   The xero query service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, XeroQuery $query) {
    $this->setConfigFactory($config_factory);
    $this->logger = $logger_factory->get('xero');
    $this->query = $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xero_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['xero.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get the configuration from ConfigFormBase::config().
    $config = self::config('xero.settings');

    // Open the fieldset if there was a problem loading the library.
    $form['oauth'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Xero Configuration'),
      '#collapsible' => TRUE,
      '#collapsed' => $this->query->hasClient() !== FALSE,
      '#tree' => TRUE,
    );

    $form['oauth']['consumer_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Xero Consumer Key'),
      '#description' => $this->t('Provide the consumer key for your private application on xero.'),
      '#default_value' => $config->get('oauth.consumer_key'),
      '#required' => TRUE,
    );

    $form['oauth']['consumer_secret'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Xero Consumer Secret'),
      '#description' => $this->t('Provide the consumer secret for your private application on xero.'),
      '#default_value' => $config->get('oauth.consumer_secret'),
      '#required' => TRUE,
    );

    $form['oauth']['key_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Xero Key Path'),
      '#description' => $this->t('Provide the full path and file name to your Xero certificate private key.'),
      '#default_value' => $config->get('oauth.key_path'),
      '#element_validate' => array(array($this, 'validateFileExists')),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validate that certificate or key file exist.
   */
  public function validateFileExists($element, FormStateInterface $form_state) {
    if (empty($element['#value']) || !file_exists($element['#value'])) {
      $form_state->setError($element, $this->t('The specified file either does not exist, or is not accessible to the web server.'));
    }
  }

  /**
   * {@inheritdoc}
   */
   public function submitForm(array &$form, FormStateInterface $form_state) {
     // Set configuration.
     $config = self::config('xero.settings');
     $form_state_values = $form_state->getValues();
     $config
       ->set('oauth.consumer_key', $form_state_values['oauth']['consumer_key'])
       ->set('oauth.consumer_secret', $form_state_values['oauth']['consumer_secret'])
       ->set('oauth.key_path', $form_state_values['oauth']['key_path']);

     $config->save();
   }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('xero.query')
    );
  }
}

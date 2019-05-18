<?php

namespace Drupal\xero\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;
use Drupal\xero\XeroQuery;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Provide configuration form for user to provide Xero API information for a
 * private application.
 */
class DefaultSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  protected $query = FALSE;

  /**
   * Inject dependencies into the form except for XeroClient because we want to
   * handle errors properly instead of failing and exploding spectacularly.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration factory interface.
   * @param \Drupal\xero\XeroQuery $query
   *   An instance of XeroClient or NULL if it fails, which is most likely the
   *   case on first load.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   Serializer object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, XeroQuery $query, Serializer $serializer, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($config_factory);
    $this->query = $query;
    $this->serializer = $serializer;
    $this->logger = $logger_factory->get('xero');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xero_default_settings_form';
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

    $account_options = array();

    try {
      $accounts = $this->query->getCache('xero_account');

      foreach ($accounts as $account) {
        // Bank accounts do not have a code, exclude them.
        if ($account->get('Code')->getValue()) {
          $account_options[$account->get('Code')->getValue()] = $account->get('Name')->getValue();
        }
      }
    }
    catch (RequestException $e) {
      $this->logger->error('%message: %response', array('%message' => $e->getMessage(), '%response' => $e->getResponse()->getBody(TRUE)));
      return parent::buildForm($form, $form_state);
    }
    catch (\Exception $e) {
      $this->logger->error('%message', array('%message' => $e->getMessage()));
      return parent::buildForm($form, $form_state);
    }

    $form['defaults']['account'] = array(
      '#type' => 'select',
      '#title' => $this->t('Default Account'),
      '#description' => $this->t('Choose a default account.'),
      '#options' => $account_options,
      '#default_value' => $config->get('defaults.account'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->query->hasClient()) {
      $form_state->setError($form['defaults'], $this->t('An error occurred trying to connect to Xero with the specified configuration. Please check the error logs for more information.'));
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
       ->set('defaults.account', $form_state_values['account']);

     $config->save();
   }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('xero.query'),
      $container->get('serializer'),
      $container->get('logger.factory')
    );
  }
}

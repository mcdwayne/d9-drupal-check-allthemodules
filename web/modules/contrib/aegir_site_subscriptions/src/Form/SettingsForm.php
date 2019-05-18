<?php

namespace Drupal\aegir_site_subscriptions\Form;

use Drupal\aegir_site_subscriptions\Plugin\SubscriptionProviderManager;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The module's configuration settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The plugin manager for subscription service providers.
   *
   * @var \Drupal\aegir_site_subscriptions\Plugin\SubscriptionProviderManager
   */
  protected $subscriptionProviderManager;

  /**
   * The messenger service for setting user-facing messages.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.aegir_site_subscription_provider'),
      $container->get('messenger')
    );
  }

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\aegir_site_subscriptions\Plugin\SubscriptionProviderManager $subscription_provider_manager
   *   The subscription provider management service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service for setting user-facing messages.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    SubscriptionProviderManager $subscription_provider_manager,
    MessengerInterface $messenger
  ) {
    parent::__construct($config_factory);
    $this->subscriptionProviderManager = $subscription_provider_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aegir_site_subscriptions_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['aegir_site_subscriptions.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('aegir_site_subscriptions.settings');

    $form['service_endpoint_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service endpoint URL'),
      '#description' => $this->t("The location of your Aegir service. HTTPS is recommended to prevent key disclosure.  As a security precaution, the hostname must be included in <em>\$settings['trusted_host_patterns']</em> in your local.settings.php."),
      '#default_value' => $config->get('service_endpoint_url'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('https://aegir.example.com/aegir/saas'),
      ],
    ];

    $form['service_endpoint_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('The key necessary to access the service endpoint. Defined in your Aegir configuration under Administration » Structure » Services » hosting_saas » Authentication.'),
      '#default_value' => $config->get('service_endpoint_key'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('super-secret-random-key'),
      ],
    ];

    $options = $this->getSubscriptionProviderPlugins();
    if (!empty($options)) {
      $form['subscription_provider'] = [
        '#type' => 'select',
        '#title' => t('Subscription provider plugin'),
        '#options' => $options,
        '#default_value' => $config->get('subscription_provider') ?: array_keys($options)[0],
        '#description' => t('Please choose your subscription provider.'),
      ];
    }
    else {
      $this->messenger->addMessage(
        $this->t('Cannot provide list of subscription provider plugins from which to choose. At least one Aegir Site Subscriptions provider submodule must be installed.'),
        MessengerInterface::TYPE_ERROR
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Fetches the list of subscription provider plugins.
   *
   * @return array
   *   The list of plugins.
   */
  protected function getSubscriptionProviderPlugins() {
    $options = [];
    foreach ($this->subscriptionProviderManager->getDefinitions() as $plugin) {
      $options[$plugin['id']] = $plugin['label'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!UrlHelper::isValid($form_state->getValue(['service_endpoint_url']), TRUE)) {
      $form_state->setErrorByName('service_endpoint_url', $this->t('The URL is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('aegir_site_subscriptions.settings')
      ->set('service_endpoint_url', $form_state->getValue('service_endpoint_url'))
      ->set('service_endpoint_key', $form_state->getValue('service_endpoint_key'))
      ->set('subscription_provider', $form_state->getValue('subscription_provider'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

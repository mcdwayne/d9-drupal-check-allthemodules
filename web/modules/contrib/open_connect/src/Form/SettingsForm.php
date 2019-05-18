<?php

namespace Drupal\open_connect\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\open_connect\Plugin\OpenConnect\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * The plugin manager.
   *
   * @var \Drupal\open_connect\Plugin\OpenConnect\ProviderManagerInterface
   */
  protected $pluginManager;

  /**
   * The instantiated plugin instances that have configuration forms.
   *
   * @var \Drupal\open_connect\Plugin\OpenConnect\Provider\ProviderInterface[]
   */
  protected $configurableProviders = [];

  /**
   * Constructs a new SettingForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\open_connect\Plugin\OpenConnect\ProviderManagerInterface $plugin_manager
   *   The plugin manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ProviderManagerInterface $plugin_manager) {
    parent::__construct($config_factory);
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.open_connect.provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['open_connect.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'open_connect_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $plugins = array_column($this->pluginManager->getDefinitions(), 'label', 'id');
    asort($plugins);

    $enabled_providers = $this->config('open_connect.settings')->get('providers');
    $form['providers'] = [
      '#title' => $this->t('Providers'),
      '#description' => $this->t('Available providers.'),
      '#type' => 'checkboxes',
      '#options' => $plugins,
      '#default_value' => array_keys($enabled_providers),
    ];

    foreach ($plugins as $id => $label) {
      $provider = $this->pluginManager->createInstance($id, isset($enabled_providers[$id]) ? $enabled_providers[$id] : []);
      if ($provider instanceof PluginFormInterface) {
        $checkbox_provider = "providers[$id]";
        $form[$id] = [
          '#title' => $label,
          '#type' => 'details',
          '#open' => TRUE,
          '#tree' => TRUE,
          '#states' => [
            'visible' => [
              ':input[name="' . $checkbox_provider . '"]' => ['checked' => TRUE],
            ],
          ],
        ];
        $subform_state = SubformState::createForSubform($form[$id], $form, $form_state);
        $form[$id] = $provider->buildConfigurationForm($form[$id], $subform_state);

        // Store the instance for validate and submit handlers.
        $this->configurableProviders[$id] = $provider;
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $enabled_providers = array_filter($form_state->getValue('providers'));
    // Let active plugins validate their settings.
    foreach ($this->configurableProviders as $id => $provider) {
      if (!isset($enabled_providers[$id])) continue;

      // The provider configuration is stored in the '$id' key in the form,
      // pass that through for validation.
      $provider->validateConfigurationForm($form[$id], SubformState::createForSubform($form[$id], $form, $form_state));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enabled_providers = array_filter($form_state->getValue('providers'));

    // Let active plugins save their settings.
    $provider_configurations = [];
    foreach ($this->configurableProviders as $id => $provider) {
      if (!isset($enabled_providers[$id])) continue;

      // The provider configuration is stored in the '$id' key in the form,
      // pass that through for submission.
      $provider->submitConfigurationForm($form[$id], SubformState::createForSubform($form[$id], $form, $form_state));
      $provider_configurations[$id] = $provider->getConfiguration();
    }
    $this->config('open_connect.settings')->set('providers', $provider_configurations)->save();

    parent::submitForm($form, $form_state);
  }

}

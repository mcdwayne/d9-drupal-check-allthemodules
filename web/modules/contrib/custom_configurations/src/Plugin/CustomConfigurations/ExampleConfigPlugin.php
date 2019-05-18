<?php

namespace Drupal\custom_configurations\Plugin\CustomConfigurations;

use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\custom_configurations\CustomConfigurationsManager;
use Drupal\custom_configurations\CustomConfigurationsPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An example custom configurations plugin.
 *
 * @Plugin(
 *   title = "Example Plugin",
 *   id = "sample",
 *   description = "Shows an example of how you can use the custom configurations plugin.",
 *   category = "Examples",
 *   weight = 0,
 *   disabled = 1,
 *   allowed_roles = {
 *     "administrator",
 *     "admin",
 *     "editor"
 *   },
 * )
 * The definition should include the following keys:
 * - id: The unique identifier of your custom plugin.
 * - title: The human-readable name for your custom plugin.
 * - description: (optional) Will be used for menu link and help information on the form page.
 * - category: (optional) The human-readable name of subcategory your custom plugin belongs to.
 * - weight: The numeric value. Will be used to arrange menu items.
 * - disabled: To enable plugin, set this option to 0.
 * - allowed_roles: An array of roles which are allowed to use your custom plugin.
 */
class ExampleConfigPlugin extends PluginBase implements CustomConfigurationsPluginInterface, ContainerFactoryPluginInterface {

  /**
   * A helper class with useful methods pertaining to custom configurations.
   *
   * @var \Drupal\custom_configurations\CustomConfigurationsManager
   */
  protected $customConfigurationsManager;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\custom_configurations\CustomConfigurationsManager $custom_configurations_manager
   *   Custom configurations service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CustomConfigurationsManager $custom_configurations_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->customConfigurationsManager = $custom_configurations_manager;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('custom_configurations.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function add(StorableConfigBase $file_config, KeyValueStoreInterface $db_config, $language) {

    $form['file_setting'] = [
      '#type' => 'textfield',
      '#title' => 'File stored setting',
      '#required' => TRUE,
      '#description' => $this->t('A sample of the setting which is stored in the configuration file.'),
      '#default_value' => $file_config->get('file_setting'),
    ];

    $form['db_setting'] = [
      '#type' => 'textfield',
      '#title' => 'Data base stored setting',
      '#required' => TRUE,
      '#description' => $this->t('A sample of the setting which is stored in the data base.'),
      '#default_value' => $db_config->get('db_setting'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(StorableConfigBase $file_config, KeyValueStoreInterface $db_config, array $values, array &$form, FormStateInterface $form_state, $language) {
    if ($values['file_setting'] == 'test') {
      $message = $this->t('Value "test" is not allowed.');
      $form_state->setError($form['sample']['file_setting'], $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submit(StorableConfigBase $file_config, KeyValueStoreInterface $db_config, array $values, array &$form, FormStateInterface $form_state, $language) {
    $file_config->set('file_setting', $values['file_setting'])->save();
    $db_config->set('db_setting', $values['db_setting']);
  }

}

<?php

namespace Drupal\admin_status\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Admin Status configuration form.
 */
class AdminStatusForm extends ConfigFormBase {

  /**
   * Holds the Admin Status plugin manager.
   *
   * @var \Drupal\admin_status\AdminStatusPluginManager
   */
  protected $adminStatusManager;

  /**
   * Constructs an AdminStatusForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The Admin Status PluginManager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PluginManagerInterface $pluginManager) {
    parent::__construct($config_factory);
    $this->adminStatusManager = $pluginManager;
  }

  /**
   * Creates the AdminStatus form.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Symfony container.
   *
   * @return static
   *   The Admin Status plugin manager.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.admin_status')
    );
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['admin_status.settings'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'admin_status_form';
  }

  /**
   * Builds the admin form.
   *
   * @param array $form
   *   A form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A form state array.
   *
   * @return array
   *   A form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('admin_status.settings');
    $plugin_status = $config->get('plugin_status');

    // Get the list of all admin status plugins defined in the system from the
    // plugin manager. Note that, at this point, what we have is *definitions*
    // of plugins, not the plugins themselves.
    $admin_status_plugin_definitions =
      $this->adminStatusManager->getDefinitions();

    // Make all the form data come back in a tree, so we can give each plugin
    // its own data.
    $form['plugins'] = ['#tree' => TRUE];

    // The array of plugin definitions is keyed by plugin id, so we can just use
    // that to load our plugins.
    foreach ($admin_status_plugin_definitions as
             $plugin_id => $admin_status_plugin_definition) {
      // We now have a plugin. From here on it can be treated just as any other
      // object (have its properties examined, methods called, etc).
      $plugin = $this->adminStatusManager->createInstance(
        $plugin_id,
        ['of' => 'configuration values']
      );

      // If there is no config data for the plugin, create a default.
      if (empty($plugin_status[$plugin_id])) {
        $plugin_status[$plugin_id] = ['enabled' => FALSE];
      }

      // Build the display name for the plugin.
      $name = !empty($admin_status_plugin_definition['name']) ?
        $admin_status_plugin_definition['name'] : $plugin_id;

      // All info for a given plugin occurs in its own fieldset.
      $form['plugins'][$plugin_id] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $name,
      ];

      $form['plugins'][$plugin_id]['description'] = [
        '#type' => 'markup',
        '#markup' => $plugin->description(),
      ];

      // Build the enable checkbox.
      $form['plugins'][$plugin_id]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable'),
        '#default_value' => $plugin_status[$plugin_id]['enabled'],
      ];

      // Get the existing config data or set a default.
      $savedConfig = $plugin_status[$plugin_id]['config'];
      if (empty($savedConfig)) {
        $savedConfig = [];
      }

      // Call the plugin to provide its config form, if any, and if there is
      // some form, put it in its own fieldset.
      $subform = $plugin->configForm([], $form_state, $savedConfig);
      if (!empty($subform)) {
        $form['plugins'][$plugin_id]['config'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Configuration settings'),
          '#states' => [
            'invisible' => [
              ':input[name="plugins[' . $plugin_id . '][enabled]"]' => ['checked' => FALSE],
            ],
          ],
        ];
        foreach ($subform as $k => $v) {
          $form['plugins'][$plugin_id]['config'][$k] = $v;
        }
      }

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validates form data.
   *
   * @param array $form
   *   A form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A form state array.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get all of the form data.
    $values = $form_state->getValues();

    // Run through each plugin's validate function.
    foreach ($values['plugins'] as $k => $v) {
      // Get the plugin.
      $plugin = $this->adminStatusManager->createInstance(
        $k,
        ['of' => 'configuration values']
      );

      // Pass in the form, form_state, and the form values for this plugin.
      $plugin->configValidateForm($form, $form_state, $v['config']);
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Handles submission of form data.
   *
   * @param array $form
   *   A form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A form state array.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get all of the form data.
    $values = $form_state->getValues();
    // Initialize array to hold config data.
    $plugin_status = [];
    foreach ($values['plugins'] as $k => $v) {
      $plugin_status[$k]['enabled'] = !empty($v['enabled']);
      $plugin = $this->adminStatusManager->createInstance(
        $k,
        ['of' => 'configuration values']
      );
      // Let the plugin build its own config data to save.
      $configValues = $plugin->configSubmitForm($form, $form_state, $v['config']);
      $plugin_status[$k]['config'] = $configValues;
    }

    // Save all config data.
    $this->config('admin_status.settings')
      ->set('plugin_status', $plugin_status)
      ->save();
  }

}

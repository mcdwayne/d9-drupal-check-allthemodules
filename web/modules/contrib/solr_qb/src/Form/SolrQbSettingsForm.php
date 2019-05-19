<?php
/**
 * @file
 * Contains Drupal\solr_qb\Form\SolrQbSettingsForm.
 */

namespace Drupal\solr_qb\Form;


use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SolrQbSettingsForm extends ConfigFormBase {

  /**
   * SolrQbDriver plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Array of all available plugins.
   *
   * @var array
   */
  protected $plugins = [];

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   SolrQbDriver plugin manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PluginManagerInterface $plugin_manager) {
    $this->setConfigFactory($config_factory);
    $this->pluginManager = $plugin_manager;

    $definitions = $this->pluginManager->getDefinitions();
    foreach ($definitions as $id => $definition) {
      $this->plugins[$id] = $this->pluginManager->createInstance($id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.solr_qb_driver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'solr_qb_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    $names = ['solr_qb.settings'];

    foreach ($this->plugins as $plugin) {
      $names[] = $plugin->getConfigName();
    }

    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('solr_qb.settings');

    $form['active_plugin'] = array(
      '#type' => 'select',
      '#title' => $this->t('Active plugin'),
      '#options' => $this->getPluginsOptions(),
      '#empty_value' => '',
      '#default_value' => $config->get('active_plugin'),
    );

    /* @var \Drupal\solr_qb\Plugin\SolrQbDriverInterface $plugin */
    foreach ($this->plugins as $id => $plugin) {
      $plugin_config = $this->config($plugin->getConfigName());

      $form[$id] = [
        '#type' => 'fieldset',
        '#title' => $plugin->getTitle(),
        '#tree' => TRUE,
        '#states' => array(
          'visible' => array(
            ':input[name="active_plugin"]' => ['value' => $id],
          ),
        ),
      ];
      $form[$id] = $plugin->buildConfigurationForm($form[$id], $plugin_config->get());
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('solr_qb.settings')
      ->set('active_plugin', $values['active_plugin'])
      ->save();

    /* @var \Drupal\solr_qb\Plugin\SolrQbDriverInterface $plugin */
    foreach ($this->plugins as $id => $plugin) {
      $config = $this->config($plugin->getConfigName());

      foreach ($values[$id] as $key => $value) {
        $config->set($key, $value);
      }

      $config->save();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Get simple array of plugins ids and names.
   *
   * @return array
   *   List of plugins ids and names.
   */
  protected function getPluginsOptions() {
    return array_map(function($plugin) {
      $definition = $plugin->getPluginDefinition();
      return $definition['title']->render();
    }, $this->plugins);
  }

}

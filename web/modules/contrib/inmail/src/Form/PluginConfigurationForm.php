<?php

namespace Drupal\inmail\Form;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Configuration form for configurable plugins.
 */
class PluginConfigurationForm extends EntityForm {

  /**
   * The injected plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The entity storage for plugin configurations.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public function __construct(PluginManagerInterface $plugin_manager, ConfigEntityStorageInterface $storage) {
    $this->pluginManager = $plugin_manager;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    /** @var \Drupal\inmail\Entity\PluginConfigEntity $entity */
    $entity = $this->getEntity();

    $form['label'] = array(
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#disabled' => !$entity->isNew(),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
      ),
    );

    $form['status'] = array(
      '#title' => $this->t('Enabled'),
      '#type' => 'checkbox',
      '#default_value' => $entity->status(),
    );

    $form['plugin_container'] = array(
      '#type' => 'container',
      '#prefix' => '<div id="inmail-plugin">',
      '#suffix' => '</div>',
    );

    // Unless editing an existing plugin config, show plugin select field.
    if ($entity->isNew()) {
      $form['plugin_container']['plugin'] = array(
        '#type' => 'select',
        '#title' => $this->t('Plugin'),
        '#options' => array_map(function(array $plugin_definition) {
          return $plugin_definition['label'];
        }, $this->pluginManager->getDefinitions()),
        '#default_value' => $entity->getPluginId(),
        '#required' => TRUE,
        '#ajax' => array(
          'callback' => '::getPluginContainerFormChild',
          'wrapper' => 'inmail-plugin',
        ),
        '#submit' => array('::submitSelectPlugin'),
        '#executes_submit_callback' => TRUE,
        '#limit_validation_errors' => array(array('plugin')),
      );

      $form['plugin_container']['plugin_submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Select plugin'),
        '#submit' => array('::submitSelectPlugin'),
        '#attributes' => array('class' => array('js-hide')),
      );
    }

    // Load plugin instance.
    if ($entity->getPluginId()) {
      $plugin = $this->pluginManager->createInstance($entity->getPluginId(), $entity->getConfiguration());
      $form_state->set('plugin', $plugin);

      // Load plugin form if it has one.
      if ($plugin instanceof PluginFormInterface) {
        $form['plugin_container']['configuration'] = $plugin->buildConfigurationForm(array(), $form_state);
      }
    }

    return $form;
  }

  /**
   * Determines if the plugin configuration already exists.
   *
   * @param string $id
   *   The plugin configuration ID.
   *
   * @return bool
   *   TRUE if the plugin config exists, FALSE otherwise.
   */
  public function exists($id) {
    return (!is_null($this->storage->load($id)));
  }

  /**
   * Submit handler for plugin selection.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitSelectPlugin(array $form, FormStateInterface $form_state) {
    $this->entity = $this->buildEntity($form, $form_state);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback returning the plugin_container part of the form.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The plugin_container form part.
   */
  public function getPluginContainerFormChild(array $form, FormStateInterface $form_state) {
    return $form['plugin_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // The 'plugin' property is definitely set, because it is #required.
    $plugin = $form_state->get('plugin');

    /** @var \Drupal\Core\Plugin\PluginFormInterface $plugin */
    if ($plugin instanceof PluginFormInterface) {
      $plugin->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // The 'plugin' property is definitely set, because it is #required.
    $plugin = $form_state->get('plugin');

    // Trigger plugin-specific submit handler. Typically, it should update the
    // plugin configuration from the form.
    if ($plugin instanceof PluginFormInterface) {
      $plugin->submitConfigurationForm($form, $form_state);
    }

    // Copy plugin configuration to the entity for persistence. The reason for
    // not doing this by overriding copyFormValuesToEntity is that the plugin
    // submit handler has to happen first.
    if ($plugin instanceof ConfigurablePluginInterface) {
      /** @var \Drupal\inmail\Entity\PluginConfigEntity $entity */
      $entity = $this->getEntity();
      $entity->setConfiguration($plugin->getConfiguration());
    }
  }

}

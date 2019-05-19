<?php

namespace Drupal\visualn\Core;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\Helpers\VisualN;
use Drupal\visualn\Core\DataGeneratorInterface;

/**
 * Base class for VisualN Data Generator plugins.
 */
abstract class DataGeneratorBase extends PluginBase implements DataGeneratorInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  abstract public function generateData();

  /**
   * {@inheritdoc}
   */
  public function generateResource() {
    $data = $this->generateData();
    $raw_input = [
      'data' => $data,
    ];

    // get resource from raw resource format plugin
    $definition = $this->getPluginDefinition();
    $raw_resource_format_id = $definition['raw_resource_format'];

    // no plugin config needed here though custom method implementation may use some
    $raw_resource_format_plugin = \Drupal::service('plugin.manager.visualn.raw_resource_format')
      ->createInstance($raw_resource_format_id, []);

    $resource = $raw_resource_format_plugin->buildResource($raw_input);

    return $resource;
  }


  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }




  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // @todo: use NestedArray::mergeDeep here. See BlockBase::setConfiguration for example.
    // @todo: also do the same for all other plugin types
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}

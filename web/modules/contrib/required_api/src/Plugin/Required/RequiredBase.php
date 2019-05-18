<?php

namespace Drupal\required_api\Plugin\Required;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\required_api\Plugin\RequiredPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class RequiredBase extends PluginBase implements RequiredPluginInterface {

  /**
   * The configuration entity's dependencies.
   *
   * @var array
   */
  protected $dependencies = array();

  /**
   * Return a form element to use in form_field_ui_field_instance_edit_form.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   A field instance.
   *
   * @return array
   *   Form element to configure the required property.
   */
  public function formElement(FieldDefinitionInterface $field) {

    $element = $this->requiredFormElement($field);

    return $element + array(
      '#prefix' => '<div id="required-ajax-wrapper">',
      '#suffix' => '</div>',
      '#parents' => array(
        'third_party_settings',
        'required_api',
        'required_plugin_options'
      ),
    );
  }

  /**
   * Helper method to get the label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Required method to get the configuration.
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Required method to set the configuration.
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
    return $this;
  }

  /**
   * Required method to set the default configuration.
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * Calculates dependencies for the configured plugin.
   *
   * @see \Drupal\Core\Config\Entity\ConfigDependencyManager
   * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::getConfigDependencyName()
   */
  public function calculateDependencies() {
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration,
        $plugin_id,
        $plugin_definition
    );
  }
}

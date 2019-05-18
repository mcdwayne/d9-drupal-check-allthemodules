<?php

namespace Drupal\jsld\Plugin\jsld;

/**
 * Base plugin for entity based structured data.
 */
abstract class JsldEntityPluginBase extends JsldPluginBase implements JsldPluginInterface {

  /**
   * An object of current entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  public $entity;

  /**
   * JsldEntityPluginBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entity = $this->configuration['entity'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Return entity object.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The current entity object.
   */
  public function entity() {
    return $this->configuration['entity'];
  }

  /**
   * Return entity type id.
   *
   * @return string
   *   Entity type name or empty string, if some problems happens.
   */
  public function getEntityTypeId() {
    if (isset($this->configuration['entity_type'])) {
      return $this->configuration['entity_type'];
    }
    elseif ($entity = $this->entity()) {
      return $entity->getEntityTypeId();
    }
    else {
      return '';
    }
  }

  /**
   * Return bundle context.
   *
   * @return string
   *   Entity bundle name.
   */
  public function bundle() {
    return $this->configuration['bundle'];
  }

  /**
   * Return view mode context.
   *
   * @return string
   *   View mode of entity requested JSON-LD.
   */
  public function viewMode() {
    return $this->configuration['view_mode'];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    if (isset($this->pluginDefinition['enabled'])) {
      return $this->pluginDefinition['enabled'];
    }
    else {
      return TRUE;
    }
  }

}
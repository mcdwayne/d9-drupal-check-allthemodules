<?php

namespace Drupal\pach\Plugin;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Base class for AccessControlHandler plugins.
 *
 * @see \Drupal\pach\Annotation\AccessControlHandler
 * @see \Drupal\pach\AccessControlHandlerPluginManager
 * @see \Drupal\pach\AccessControlHandlerInterface
 * @see plugin_api
 */
abstract class AccessControlHandlerBase extends PluginBase implements AccessControlHandlerInterface {

  /**
   * The plugin settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * Name of the entity type the handler controls access for.
   *
   * @var string
   */
  protected $type;

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
  public function setConfiguration(array $configuration) {
    if (isset($configuration['type'])) {
      $this->type = $configuration['type'];
    }
    if (isset($configuration['settings'])) {
      $this->settings = (array) $configuration['settings'];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'type' => $this->type,
      'settings' => $this->settings,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'type' => $this->pluginDefinition['type'],
      'settings' => $this->pluginDefinition['settings'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccessResultInterface &$acccess, EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    // Override this method to alter the access result.
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess(AccessResultInterface &$acccess, $entity_bundle = NULL, AccountInterface $account = NULL, array $context = []) {
    // Override this method to alter the access result.
  }

  /**
   * {@inheritdoc}
   */
  public function fieldAccess(AccessResultInterface &$acccess, $operation, FieldDefinitionInterface $field_definition, AccountInterface $account = NULL, FieldItemListInterface $items = NULL) {
    // Override this method to alter the access result.
  }

}

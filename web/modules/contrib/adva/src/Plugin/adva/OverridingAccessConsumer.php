<?php

namespace Drupal\adva\Plugin\adva;

use Drupal\adva\AccessStorage;
use Drupal\adva\AdvancedAccessEntityAccessControlHandler;
use Drupal\adva\Entity\AccessConsumerInterface as AccessConsumerEntityInterface;
use Drupal\adva\Plugin\adva\Manager\AccessProviderManagerInterface;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends access consumer defintion to override the entity's access handler.
 */
class OverridingAccessConsumer extends AccessConsumer implements OverridingAccessConsumerInterface {

  use DependencySerializationTrait;

  /**
   * Access Storage Service.
   *
   * @var \Drupal\adva\AccessStorage
   */
  protected $accessStorage;

  /**
   * State Service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Init Access Consumer instance.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Unique plugin id.
   * @param array|mixed $plugin_definition
   *   Plugin instance definition.
   * @param \Drupal\adva\Plugin\adva\Manager\AccessProviderManagerInterface $provider_manager
   *   Access Provider plugin manager.
   * @param \Drupal\adva\AccessStorage $access_storage
   *   Access Storage service.
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessProviderManagerInterface $provider_manager, AccessStorage $access_storage, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $provider_manager);
    $this->accessStorage = $access_storage;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get("plugin.manager.adva.provider"),
      $container->get("adva.access_storage"),
      $container->get("state")
    );
  }

  /**
   * {@inheritdoc}
   */
  public function overrideAccessControlHandler(EntityTypeInterface $entityType) {
    $_class = $entityType->getHandlerClass("access");
    if (!$_class) {
      throw new InvalidPluginDefinitionException($entityType, sprintf('The "%s" entity type did not specify a %s handler.', $entityType, $entityType));
    }
    $entityType->setHandlerClass(AdvancedAccessEntityAccessControlHandler::LEGACY_HANDLER_ID, $_class);
    // Override Access Class to use the AdvancedAccess Handler.
    $entityType->setAccessClass('\Drupal\adva\AdvancedAccessEntityAccessControlHandler');
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildCache($batch_mode = FALSE) {
    $this->accessStorage->rebuild($this, $batch_mode);
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildRequired($rebuild = NULL) {
    $stateId = 'adva_access.needs_rebuild.' . $this->getEntityTypeId();
    if (!isset($rebuild)) {
      return $this->state->get($stateId) ?: FALSE;
    }
    elseif ($rebuild) {
      $this->state->set($stateId, TRUE);
    }
    else {
      $this->state->delete($stateId);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onChange(AccessConsumerEntityInterface $config) {
    $this->rebuildRequired(TRUE);
  }

}

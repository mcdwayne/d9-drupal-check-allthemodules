<?php

namespace Drupal\adva\Plugin\adva;

use Drupal\adva\Entity\AccessConsumerInterface as AccessConsumerEntityInterface;
use Drupal\adva\Plugin\adva\Manager\AccessProviderManagerInterface;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an plugin base for AccessConsumer Plugins.
 */
class AccessConsumer extends PluginBase implements AccessConsumerInterface, ContainerFactoryPluginInterface {

  /**
   * Name of the entity type the consumer enables Advanced Access for.
   *
   * @var string
   */
  private $entityType;

  /**
   * List of enabled access provider ids.
   *
   * @var \Drupal\adva\Annotation\AccessProviderInterface[]
   */
  protected $accessProviders;

  /**
   * Access provider plugin manager.
   *
   * @var \Drupal\adva\Plugin\adva\Manager\AccessProviderManagerInterface
   */
  private $providerManager;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessProviderManagerInterface $provider_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->providerManager = $provider_manager;

    // Load config from definition.
    $this->entityType = $this->getPluginDefinition()["entityType"];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get("plugin.manager.adva.provider")
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getProviderManager() {
    return $this->providerManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessProviderIds(array $provider_ids) {
    $this->configuration["providers"] = $provider_ids;
    unset($this->accessProviders);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessProviderIds() {
    return $this->configuration["providers"] ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAllAccessProviderConfig() {
    return $this->configuration["provider_config"] ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessProviderConfig($provider_id) {
    return isset($this->getAllAccessProviderConfig()[$provider_id]) ? $this->getAllAccessProviderConfig()[$provider_id] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessProviderConfig($provider_id, $config) {
    $this->configuration["provider_config"][$provider_id] = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessProviders() {
    if (!isset($this->accessProviders)) {
      $this->accessProviders = $this->getProviderManager()->getProviders($this);
    }
    return $this->accessProviders;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->configuration["settings"];
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->configuration["settings"] = $settings;
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
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $records = $this->getAccessRecords($entity);
    $grants = $this->getAccessGrants($operation, $account);
    foreach ($records as $record) {
      $relm = $record["relm"];
      $gid = $record["gid"];
      if (isset($grants[$record[$relm]]) && in_array($gid, $grants[$record[$relm]])) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessRecords(EntityInterface $entity) {
    $records = [];
    foreach ($this->getAccessProviders() as $provider) {
      $new_records = $provider->getAccessRecords($entity);
      if (!empty($new_records)) {
        $records = array_merge($records, $new_records);
      }
    }
    return $records;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessGrants($operation, AccountInterface $account) {
    $grants = [];
    foreach ($this->getAccessProviders() as $provider) {
      if (in_array($operation, $provider->getOperations())) {
        $new_grants = $provider->getAccessGrants($operation, $account);
        if (isset($new_grants)) {
          $grants = array_merge_recursive($grants, $new_grants);
        }
      }
    }
    return $grants;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange(AccessConsumerEntityInterface $config) {
    // Do nothing by default.
  }

}

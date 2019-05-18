<?php

namespace Drupal\adva\Plugin\adva;

use Drupal\adva\Entity\AccessConsumerInterface as AccessConsumerEntityInterface;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines implmentation of AccessConsumer plugin.
 */
interface AccessConsumerInterface {

  /**
   * Retrieve the instance of the access provider plugin manager.
   *
   * @return \Drupal\adva\Plugin\adva\Manager\AccessProviderManagerInterface
   *   Plugin Manager instance.
   */
  public function getProviderManager();

  /**
   * Get entity type id that the plugin enables advanced access control for.
   *
   * @return string
   *   Entity Id.
   */
  public function getEntityTypeId();

  /**
   * Get list of configured access providers.
   *
   * @return array
   *   Ids of access providers enabled for this consumer.
   */
  public function getAccessProviderIds();

  /**
   * Get list of configured access providers.
   *
   * @param array $provider_ids
   *   Ids of access providers enabled for this consumer.
   */
  public function setAccessProviderIds(array $provider_ids);

  /**
   * Get config for providers.
   *
   * @return array
   *   Config for provider instances.
   */
  public function getAllAccessProviderConfig();

  /**
   * Get config for a provider.
   *
   * @param string $provider_id
   *   Id of access provider.
   *
   * @return array
   *   Config for provider instance.
   */
  public function getAccessProviderConfig($provider_id);

  /**
   * Save the config for a given provider.
   *
   * @param string $provider_id
   *   Id of access provider.
   * @param mixed $config
   *   Config value for the provider.
   */
  public function setAccessProviderConfig($provider_id, $config);

  /**
   * Get access provider plugin instances.
   *
   * On the first run the instances are initialized from the manager.
   *
   * @return \Drupal\adva\Annotation\AccessProvider[]
   *   Instances of access providers for this class.
   */
  public function getAccessProviders();

  /**
   * Get instance settings configuration.
   *
   * @return array
   *   Settings array from configuration.
   */
  public function getSettings();

  /**
   * Update configuration with new settings.
   *
   * @param array $settings
   *   Settings array.
   */
  public function setSettings(array $settings);

  /**
   * Get instance configuration.
   *
   * @return array
   *   Configuration array containing settings, provider id and their config.
   */
  public function getConfiguration();

  /**
   * Check access to an entity for a given user to do an operation.
   *
   * Check if any configured providers grant access to perform the entity
   * operation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   Entity operation being performed.
   *   Usually one of "view", "view label", "update" or "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result. Returns an AccessResultInterface object.
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account);

  /**
   * Get access records for a given entity.
   *
   * Retreives the list of providers configured for the entity type's consumer,
   * and retrieves the records for each provider.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to retrieve grants.
   *
   * @return array
   *   An array of grants.
   */
  public function getAccessRecords(EntityInterface $entity);

  /**
   * Get a user's access grants for a given operation by configured providers.
   *
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually one of "view", "view label", "update" or "delete".
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return array
   *   An array whose keys are "realms" of grants, and whose values are arrays
   *   of the grant IDs within this realm that this user is being granted.
   */
  public function getAccessGrants($operation, AccountInterface $account);

  /**
   * Respond to changes in the config entity.
   *
   * @param \Drupal\adva\Entity\AccessConsumerInterface $config
   *   Config entity for the consumer's config storage.
   */
  public function onChange(AccessConsumerEntityInterface $config);

}

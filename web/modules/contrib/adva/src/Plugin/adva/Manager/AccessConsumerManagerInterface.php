<?php

namespace Drupal\adva\Plugin\adva\Manager;

use Drupal\adva\Plugin\adva\AccessProviderInterface;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines interface for AccessConsumerManager.
 *
 * @see \Drupal\adva\Annotation\AccessConsumer.
 */
interface AccessConsumerManagerInterface {

  /**
   * Get all access consumer config storage.
   *
   * Returns the entity storage instance for AccessConsumer config entities.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage instance.
   */
  public function getConsumerStorage();

  /**
   * Get all access consumers.
   *
   * Returns a list of AccessConsumer plugins.
   *
   * @return \Drupal\adva\Plugin\adva\AccessConsumerInterface[]
   *   Array of all available consumer plugins.
   */
  public function getConsumers();

  /**
   * Get consumer plugin by id.
   *
   * @param string $id
   *   Id for plugin.
   *
   * @return \Drupal\adva\Plugin\adva\AccessConsumerInterface
   *   Plugin for entity type.
   */
  public function getConsumer($id);

  /**
   * Get a list of comsumers configured to use a particular provider.
   *
   * @param int $providerId
   *   The id for the access provider being queried.
   *
   * @return \Drupal\adva\Annotation\AccessConsumerInterface[]
   *   Access Consumer objects configured to use the provider.
   */
  public function getConsumersForProviderId($providerId);

  /**
   * Get a list of comsumers configured to use a particular provider.
   *
   * @param \Drupal\adva\Annotation\AccessProviderInterface $provider
   *   Access provider being queried.
   *
   * @return \Drupal\adva\Annotation\AccessConsumerInterface[]
   *   Access Consumer objects configured to use the provider.
   */
  public function getConsumersForProvider(AccessProviderInterface $provider);

  /**
   * Get consumer plugin for a specific entity type by id.
   *
   * @param string $entityTypeId
   *   Name of entity type to retrieve consumer for.
   *
   * @return \Drupal\adva\Plugin\adva\AccessConsumerInterface
   *   Plugin for entity type.
   */
  public function getConsumerForEntityTypeId($entityTypeId);

  /**
   * Get consumer plugin for a specific entity type.
   *
   * @return \Drupal\adva\Plugin\adva\AccessConsumerInterface
   *   Plugin for entity type.
   */
  public function getConsumerForEntityType(EntityTypeInterface $entityType);

  /**
   * Check if a given entity type has a consumer plugin.
   *
   * @return bool
   *   TRUE if a comsumer plugin exists.
   */
  public function entityTypeHasConsumer($entityTypeId);

  /**
   * Gets configured list of providers for a entity type.
   *
   * @param string $entityTypeId
   *   Id of entity type to get providers for.
   *
   * @return array
   *   Array of provider plugin ids.
   */
  public function getProviders($entityTypeId);

  /**
   * Gets configured list of providers for a entity type.
   *
   * @return array
   *   Array of provider plugin ids.
   */
  public function getEntityTypeProviders(EntityTypeInterface $entityType);

  /**
   * Get all override consumers.
   *
   * Gets a list of override consumer plugins. Determins this based on if the
   * instance implements OverridingAccessConsumerInterface.
   */
  public function getOverrideConsumers();

  /**
   * Checks if the a given entityType has an override consumer plugin.
   *
   * @retun bool
   *   TRUE if the types consumer plugin overrides the default access handler.
   */
  public function hasOverrideConsumer($entityTypeId);

  /**
   * Save config for consumer instances to the database.
   *
   * Updates the AccessConsumer config entities for each of the active consumers
   * instances and saves them.
   */
  public function saveConsumers();

}

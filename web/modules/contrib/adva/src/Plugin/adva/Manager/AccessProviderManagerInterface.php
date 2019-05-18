<?php

namespace Drupal\adva\Plugin\adva\Manager;

use Drupal\adva\Plugin\adva\AccessConsumerInterface;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines interface for AccessProviderManager.
 *
 * @see \Drupal\adva\Annotation\AccessProvider.
 */
interface AccessProviderManagerInterface {

  /**
   * Get available providers for an entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   EntityType to check provider availablity for.
   *
   * @return array
   *   Array of providers ids that support a given entity type.
   */
  public function getAvailableProvidersForEntityType(EntityTypeInterface $entityType);

  /**
   * Retrieve provider plugins.
   *
   * Retreives all provider plugins for a given consumer.
   *
   * @param \Drupal\adva\Annotation\AccessConsumerInterface $consumer
   *   Access consumer to init plugins for.
   *
   * @return Drupal\adva\Annotation\AccessProviderInterface
   *   Instances of access provider plugins.
   */
  public function getProviders(AccessConsumerInterface $consumer);

  /**
   * Get the class for a given definition.
   *
   * @param string $definition_id
   *   Definition id string.
   * @param array $definition
   *   Definition data.
   *
   * @return string
   *   Definition class.
   */
  public function getDefinitionClass($definition_id, array $definition);

}

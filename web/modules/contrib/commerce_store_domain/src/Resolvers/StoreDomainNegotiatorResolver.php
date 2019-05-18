<?php

namespace Drupal\commerce_store_domain\Resolvers;

use Drupal\commerce_store\Resolver\StoreResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\domain\DomainNegotiatorInterface;

class StoreDomainNegotiatorResolver implements StoreResolverInterface {

  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The store storage.
   *
   * @var \Drupal\commerce_store\StoreStorageInterface
   */
  protected $storage;

  /**
   * Constructs a CurrentDomainContext object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(DomainNegotiatorInterface $negotiator, EntityTypeManagerInterface $entity_type_manager) {
    $this->negotiator = $negotiator;
    $this->storage = $entity_type_manager->getStorage('commerce_store');
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    $current_domain = $this->negotiator->getActiveDomain();

    // No active domain was determined.
    if (!$current_domain) {
      return NULL;
    }

    $query = $this->storage->getQuery();
    $query->condition('domain_entity', $current_domain->id());
    $store_ids = $query->execute();
    if (!empty($store_ids)) {
      $store_id = reset($store_ids);
      return $this->storage->load($store_id);
    }
  }

}

<?php

namespace Drupal\commerce_country_store\Resolver;

use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_store\Resolver\StoreResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\geoip\GeoLocation;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns the default store, if known.
 */
class PrefixStoreResolver implements StoreResolverInterface {

  /**
   * The store storage.
   *
   * @var \Drupal\commerce_store\StoreStorageInterface
   */
  protected $storage;

  /**
   * The current request
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Constructs a new DefaultStoreResolver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $requestStack) {
    $this->storage = $entity_type_manager->getStorage('commerce_store');
    $this->currentRequest = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    $request_path = urldecode(trim($this->currentRequest->getPathInfo(), '/'));
    $path_args = explode('/', $request_path);
    $prefix = array_shift($path_args);

    foreach ($this->storage->loadMultiple() as $store) {
      /** @var Store $store */
      if ($store->hasField('path_prefix') && $store->path_prefix->value == $prefix) {
        return $store;
      }
    }
  }

}

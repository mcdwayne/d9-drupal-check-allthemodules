<?php

namespace Drupal\commerce_country_store;

use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_store\CurrentStore;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor for url_alter_test.
 */
class PathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * @var \Drupal\commerce_store\CurrentStore
   */
  protected $currentStore;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, CurrentStore $currentStore) {
    $this->storage = $entity_type_manager->getStorage('commerce_store');
    $this->currentStore = $currentStore;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $parts = explode('/', trim($path, '/'));
    $prefix = array_shift($parts);

    // Search prefix within stores.
    foreach ($this->storage->loadMultiple() as $store) {
      /** @var Store $store */
      if ($store->hasField('path_prefix') && $store->path_prefix->value == $prefix) {
        // Rebuild $path with the store prefix removed.
        $path = '/' . implode('/', $parts);

        return $path;
      }
    }
    
    return $path;
  }

  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {

    if (isset($options['store'])) {
      $store = $options['store'];
    }
    else {
      $store = $this->currentStore->getStore();
    }

    if ($store && $store->hasField('path_prefix')) {
      if (!empty($store->path_prefix->value)) {
        $options['prefix'] = $store->path_prefix->value . '/';
      }
    }

    return $path;
  }

}

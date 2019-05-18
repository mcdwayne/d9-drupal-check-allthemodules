<?php

namespace Drupal\active_cache\EventSubscriber;

use Drupal\active_cache\Plugin\ActiveCacheManager;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ActiveCacheRebuildSubscriber
 * @package Drupal\active_cache\EventSubscriber
 */
class ActiveCacheRebuildSubscriber implements EventSubscriberInterface, ContainerInjectionInterface {

  /**
   * @var \Drupal\active_cache\Plugin\ActiveCacheManager
   */
  protected $activeCacheManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ActiveCacheManager $active_cache_manager) {
    $this->activeCacheManager = $active_cache_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.active_cache'));
  }


  /**
   * Rebuilds all the caches that support active_cache after the page was served.
   */
  public function rebuildCaches() {
    foreach ($this->activeCacheManager->getDefinitions() as $id => $definition) {
      $active_cache = $this->activeCacheManager->getInstance(['id' => $id]);
      $active_cache->getData();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::TERMINATE => 'rebuildCaches',
    ];
  }

}
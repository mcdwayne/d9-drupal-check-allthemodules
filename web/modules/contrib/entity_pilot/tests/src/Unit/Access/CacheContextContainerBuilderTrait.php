<?php

namespace Drupal\Tests\entity_pilot\Unit\Access;

use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Defines a trait for simplifying setup of the cache contexts manager.
 */
trait CacheContextContainerBuilderTrait {

  /**
   * The cache contexts manager.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheContextsManager;

  /**
   * Sets up cache context manager and container for testing purposes.
   */
  public function setupCacheContextsManagerAndContainer() {

    $this->cacheContextsManager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->cacheContextsManager->expects($this->any())
      ->method('assertValidTokens')
      ->willReturn(TRUE);

    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $this->cacheContextsManager);
    \Drupal::setContainer($container);
  }

}

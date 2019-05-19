<?php

namespace Drupal\streamy;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides an Streamy Stream plugin manager.
 *
 * @see plugin_api
 */
class StreamyStreamManager extends DefaultPluginManager {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * StreamyStreamManager constructor.
   *
   * @param \Traversable                                   $namespaces
   * @param \Drupal\Core\Cache\CacheBackendInterface       $cache_backend
   * @param \Drupal\Core\Extension\ModuleHandlerInterface  $module_handler
   * @param \Drupal\Core\Config\ConfigFactoryInterface     $configFactory
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   * @param \Psr\Log\LoggerInterface                       $logger
   */
  public function __construct(\Traversable $namespaces,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler,
                              ConfigFactoryInterface $configFactory,
                              RequestStack $requestStack,
                              LoggerInterface $logger) {
    parent::__construct(
      'Plugin/StreamyStream',
      $namespaces,
      $module_handler,
      'Drupal\\streamy\\StreamyStreamInterface',
      'Drupal\\streamy\\Annotation\\StreamyStream'
    );
    $this->alterInfo('streamy_streamystream_info');
    $this->setCacheBackend($cache_backend, 'streamy_streamystream_info_plugins');
    $this->configFactory = $configFactory;
    $this->request = $requestStack;
    $this->logger = $logger;
  }

  /**
   * @inheritdoc
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $instance = parent::createInstance($plugin_id, $configuration);
    $instance->setConfigFactory($this->configFactory);
    $instance->setLogger($this->logger);
    $instance->setRequest($this->request->getCurrentRequest());
    $instance->setUp();
    return $instance;
  }

}

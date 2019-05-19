<?php

namespace Drupal\xhprof;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class StorageFactory
 */
class StorageFactory {

  /**
   *
   */
  public function __construct() {
    $this->storages = array();
  }

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\xhprof\XHProfLib\Storage\StorageInterface
   */
  final public static function getStorage(ConfigFactoryInterface $config, ContainerInterface $container) {
    $storage = $config->get('xhprof.config')
      ->get('storage') ?: 'xhprof.file_storage';

    return $container->get($storage);
  }

}

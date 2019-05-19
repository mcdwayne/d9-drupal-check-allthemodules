<?php

namespace Drupal\varnish\Cache;


use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Path\AliasManager;

class VarnishBackendFactory implements CacheFactoryInterface{

  protected $pathAliasManager;

  function __construct(AliasManager $pathAliasManager) {
    $this->pathAliasManager = $pathAliasManager;
  }


  /**
   * {@inheritdoc}
   */
  public function get($bin) {
    return new VarnishBackend($bin, $this->pathAliasManager);
  }

}

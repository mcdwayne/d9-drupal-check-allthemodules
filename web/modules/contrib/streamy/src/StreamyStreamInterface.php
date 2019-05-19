<?php

namespace Drupal\streamy;

use League\Flysystem\MountManager;

interface StreamyStreamInterface {

  /**
   * Returns a flysystem Adapter.
   *
   * @param string $scheme
   * @param array  $config
   * @return \League\Flysystem\AdapterInterface
   */
  public function getAdapter(string $scheme, string $level, array $config = []);

  /**
   * Returns a valid external URL or null.
   *
   * @param                                $uri
   * @param string                         $scheme
   * @param \League\Flysystem\MountManager $readFileSystem
   * @param                                $adapter
   * @return string|null
   */
  public function getExternalUrl($uri, string $scheme, string $level, MountManager $readFileSystem, $adapter);

}

<?php

namespace Drupal\cache_consistent\Cache;

/**
 * Interface CacheConsistentBufferInterface.
 *
 * @package Drupal\cache_consistent\Cache
 *
 * @ingroup cache_consistent
 */
interface CacheConsistentFactoryInterface {

  /**
   * Get service name for a cache bin.
   *
   * @param string $bin
   *   The bin to get the service name of.
   *
   * @return string
   *   The name of the service for this bin.
   */
  public function getServiceName($bin);

  /**
   * Check if a service name is intrinsically consistent.
   *
   * @param string $service_name
   *   Name of the service.
   *
   * @return bool
   *   TRUE if service name is considered intrinsically consistent.
   */
  public function isServiceConsistent($service_name);

}

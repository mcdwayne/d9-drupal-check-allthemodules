<?php

namespace Drupal\browscap;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Database;

/**
 * Class BrowscapService.
 *
 * @package Drupal\browscap
 */
class BrowscapService {

  /**
   * Config Factory Interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * A cache backend interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Client constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config Factory Interface.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend instance to use.
   */
  public function __construct(ConfigFactoryInterface $config, CacheBackendInterface $cache) {
    $this->config = $config;
    $this->cache = $cache;
  }

  /**
   * Provide data about a user agent string or the current user agent.
   *
   * @param string $user_agent
   *   Optional user agent string to test. If empty, use the value from the
   *   current request.
   *
   * @return array
   *   An array of information about the user agent.
   */
  public function getBrowser($user_agent = NULL) {

    // Determine the current user agent if a user agent was not specified.
    if ($user_agent != NULL) {
      $user_agent = Html::escape(trim($user_agent));
    }
    elseif ($user_agent == NULL && isset($_SERVER['HTTP_USER_AGENT'])) {
      $user_agent = Html::escape(trim($_SERVER['HTTP_USER_AGENT']));
    }
    else {
      $user_agent = 'Default Browser';
    }

    // Check the cache for user agent data.
    $cache_data = $this->cache->get($user_agent);

    // Attempt to find a cached user agent.
    // Otherwise store the user agent data in the cache.
    if (!empty($cache_data) && ($cache_data->created > REQUEST_TIME - 60 * 60 * 24)) {
      $user_agent_properties = unserialize($cache_data->data);
    }
    else {
      // Find the user agent's properties.
      // The useragent column contains the wildcarded pattern to match against
      // our full-length string while the ORDER BY chooses the most-specific
      // matching pattern.
      $user_agent_properties = Database::getConnection()->query("SELECT * FROM {browscap} WHERE :useragent LIKE useragent ORDER BY LENGTH(useragent) DESC", [':useragent' => $user_agent])
        ->fetchObject();

      // Serialize the property data for caching.
      $serialized_property_data = serialize($user_agent_properties);

      // Store user agent data in the cache.
      $this->cache->set($user_agent, $serialized_property_data);
    }

    // Create an array to hold the user agent's properties.
    $properties = [];

    // Return an array of user agent properties.
    if (isset($user_agent_properties)) {
      // Unserialize the user agent data found in the cache or the database.
      $properties = unserialize($user_agent_properties->data);

      // Set the user agent name and name pattern.
      $properties['useragent'] = $user_agent;
      $properties['browser_name_pattern'] = strtr($user_agent_properties->useragent, '%_', '*?');
    }
    else {
      // Set the user agent name and name pattern to 'unrecognized'.
      $properties['useragent'] = 'unrecognized';
      $properties['browser_name_pattern'] = strtr('unrecognized', '%_', '*?');
    }

    return $properties;
  }

}

<?php

/**
 * @file
 * Contains form for output of memcached usage statistics.
 */

namespace Drupal\memcache_storage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\Core\Cache\Cache;
use Drupal\memcache_storage\DrupalMemcachedUtils;


class MemcachedStatistics extends ControllerBase {

  public function content() {

    $settings = Settings::get('memcache_storage');

    $clusters = [];
    if (!empty($settings['memcached_servers'])) {
      $clusters = array_unique(array_values($settings['memcached_servers']));
    }

    $clusters_stats = [];
    foreach ($clusters as $cluster_name) {

      // Initializes a new DrupalMemcache(d) object.
      // TODO: Switch to services.
      $pecl_extension = DrupalMemcachedUtils::getPeclExtension();
      $class_name = 'Drupal\memcache_storage\Drupal' . ucfirst(strtolower($pecl_extension));
      $memcached = new $class_name($settings, $cluster_name);
      $clusters_stats[$cluster_name] = $memcached->getStats();
    }

    $build = [];
    $build['general'] = [
      '#type' => 'table',
      '#title' => t('General information'),
      '#header' => [t('Cluster'), t('Servers'), t('Cache bins')],
      '#empty' => t('There are no memcached servers configured.'),
    ];

    $cache_settings = Settings::get('cache');
    $memcached_bins = [];
    $default_cache_service = isset($cache_settings['default']) ? $cache_settings['default'] : 'cache.backend.database';
    foreach (Cache::getBins() as $bin => $bin_settings) {
      $service_name = isset($cache_settings['bins'][$bin]) ? $cache_settings['bins'][$bin] : $default_cache_service;
      if ($service_name == 'cache.backend.memcache_storage') {
        $bin = 'cache_' . $bin;
        $bin_cluster = !empty($settings['bins_clusters'][$bin]) ? $settings['bins_clusters'][$bin] : 'default';
        $memcached_bins[$bin_cluster][] = $bin;
      }
    }

    foreach ($clusters_stats as $cluster_name => $cluster_stats) {

      $servers = array_keys($cluster_stats);
      $servers_list = !empty($servers) ? implode(', ', $servers) : t('No servers specified.');
      $bins_list = !empty($memcached_bins[$cluster_name]) ? implode(', ', $memcached_bins[$cluster_name]) : t('No mapped cache bins.');
      unset($memcached_bins[$cluster_name]);

      $build['general']['#rows'][] = [
        $cluster_name,
        $servers_list,
        $bins_list,
      ];
    }

    foreach ($memcached_bins as $cluster_name => $cache_bins) {
      $build['general']['#rows'][] = [
        $cluster_name,
        t('No servers specified.'),
        implode(', ', $cache_bins),
      ];
    }


    return $build;
  }
}

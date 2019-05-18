<?php

namespace Drupal\opcache_gui;

use function format_size;

class OpCache {
  protected $data;
  protected $options;
  protected $defaults = [
    'allow_filelist' => TRUE,
    'allow_invalidate' => TRUE,
    'allow_reset' => TRUE,
    'allow_realtime' => TRUE,
    'refresh_time' => 5,
    'size_precision' => 2,
    'charts' => TRUE,
    'debounce_rate' => 250
  ];

  public function __construct(array $options = []) {
    $this->options = array_merge($this->defaults, $options);
    $this->data = $this->compileState();
  }

  public function getOption(string $name = NULL) {
    if ($name === NULL) {
      return $this->options;
    }
    return (isset($this->options[$name])
      ? $this->options[$name]
      : NULL
    );
  }

  public function getData(string $section = NULL, string $property = NULL) {
    if ($section === NULL) {
      return $this->data;
    }
    $section = strtolower($section);
    if (isset($this->data[$section])) {
      if ($property === NULL || !isset($this->data[$section][$property])) {
        return $this->data[$section];
      }
      return $this->data[$section][$property];
    }
    return NULL;
  }

  public function isInvalidationAllowed() {
    return ($this->getOption('allow_invalidate') && function_exists('opcache_invalidate'));
  }

  public function resetCache(string $file = NULL) {
    $success = FALSE;
    if ($file === NULL) {
      $success = opcache_reset();
    }
    else {
      if (function_exists('opcache_invalidate')) {
        $success = opcache_invalidate(urldecode($file), TRUE);
      }
    }
    if ($success) {
      $this->compileState();
    }
    return $success;
  }

  /**
   * @todo Put this in a separate service.
   *
   * @param float $size
   *
   * @return string
   */
  protected function size($size) {
    return format_size($size);
  }

  protected function compileState() {
    $status = opcache_get_status();
    $config = opcache_get_configuration();

    $files = [];
    if (!empty($status['scripts']) && $this->getOption('allow_filelist')) {
      uasort($status['scripts'], function ($a, $b) {
        return $a['hits'] < $b['hits'];
      });
      foreach ($status['scripts'] as &$file) {
        $file['full_path'] = str_replace('\\', '/', $file['full_path']);
        $file['readable'] = [
          'hits' => number_format($file['hits']),
          'memory_consumption' => $this->size($file['memory_consumption'])
        ];
      }
      $files = array_values($status['scripts']);
    }

    // @todo Use Drupal date and number formatting functions below.
    $overview = array_merge(
      $status['memory_usage'], $status['opcache_statistics'], [
        'used_memory_percentage' => round(100 * (
            ($status['memory_usage']['used_memory'] + $status['memory_usage']['wasted_memory'])
            / $config['directives']['opcache.memory_consumption'])),
        'hit_rate_percentage' => round($status['opcache_statistics']['opcache_hit_rate']),
        'wasted_percentage' => round($status['memory_usage']['current_wasted_percentage'],
          2),
        'readable' => [
          'total_memory' => $this->size($config['directives']['opcache.memory_consumption']),
          'used_memory' => $this->size($status['memory_usage']['used_memory']),
          'free_memory' => $this->size($status['memory_usage']['free_memory']),
          'wasted_memory' => $this->size($status['memory_usage']['wasted_memory']),
          'num_cached_scripts' => number_format($status['opcache_statistics']['num_cached_scripts']),
          'hits' => number_format($status['opcache_statistics']['hits']),
          'misses' => number_format($status['opcache_statistics']['misses']),
          'blacklist_miss' => number_format($status['opcache_statistics']['blacklist_misses']),
          'num_cached_keys' => number_format($status['opcache_statistics']['num_cached_keys']),
          'max_cached_keys' => number_format($status['opcache_statistics']['max_cached_keys']),
          'start_time' => date('Y-m-d H:i:s',
            $status['opcache_statistics']['start_time']),
          'last_restart_time' => ($status['opcache_statistics']['last_restart_time'] == 0
            ? 'never'
            : date('Y-m-d H:i:s',
              $status['opcache_statistics']['last_restart_time'])
          )
        ]
      ]
    );

    $directives = [];
    ksort($config['directives']);
    foreach ($config['directives'] as $k => $v) {
      $directives[] = ['k' => $k, 'v' => $v];
    }

    $version = array_merge(
      $config['version'],
      [
        'php' => phpversion(),
        'server' => $_SERVER['SERVER_SOFTWARE'],
        'host' => (function_exists('gethostname')
          ? gethostname()
          : (php_uname('n')
            ?: (empty($_SERVER['SERVER_NAME'])
              ? $_SERVER['HOST_NAME']
              : $_SERVER['SERVER_NAME']
            )
          )
        )
      ]
    );

    return [
      'version' => $version,
      'overview' => $overview,
      'files' => $files,
      'directives' => $directives,
      'blacklist' => $config['blacklist'],
      'functions' => get_extension_funcs('Zend OPcache')
    ];
  }

}

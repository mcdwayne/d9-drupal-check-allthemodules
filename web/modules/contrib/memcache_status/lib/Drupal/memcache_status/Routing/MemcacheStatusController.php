<?php

/**
 * @file
 * Contains \Drupal\memcache_status\Routing\MemcacheStatusController.
 */

namespace Drupal\memcache_status\Routing;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\ControllerInterface;

/**
 * Returns responses for Memcache Status module routes.
 */
class MemcacheStatusController implements ControllerInterface {

  /**
   * {inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Presents the Memcache Status report page. This will only return HTML in the
   * case of an error; otherwise, the output resulting from memcache.php.inc is
   * directly output, and Drupal is terminated in the usual way.
   */
  public function memcacheReport() {
    // Locate memcache.php.inc.
    // @todo Once Libraries 8.x is available, implement this properly.
    // $memcache_php = libraries_get_path('memcache') . '/memcache.php.inc';
    $memcache_php = __DIR__ . '/../../../../memcache.php.inc';
    if (!is_file($memcache_php)) {
      $output = t('<code>memcache.php.inc</code> not found.  Please download and extract <a href="@memcache-url">memcache</a>, rename <code>memcache.php</code> to <code>memcache.php.inc</code> and place the file in a directory named <code>memcache</code> that <a href="@libraries-api-url">Libraries API</a> can find (i.e., in <code>sites/all/libraries/memcache</code>).', array('@memcache-url' => "http://pecl.php.net/package/memcache", '@libraries-api-url' => "http://drupal.org/project/libraries"));
      return $output;
    }

    // Hacks to get the memcache.php file working.
    global $MEMCACHE_SERVERS, $PHP_SELF;
    $_SERVER['PHP_SELF'] = url('admin/reports/status/memcache');
    $PHP_SELF = $_SERVER['PHP_SELF'];
    $_SERVER['PHP_AUTH_USER'] = 'memcache';
    $_SERVER['PHP_AUTH_PW'] = 'password';
    // @todo Once Memcache 8.x is available, implement this properly.
    // $MEMCACHE_SERVERS = array_keys(config('memcache.settings')->get('servers', array()));
    $MEMCACHE_SERVERS = array('localhost:11211');

    // We cannot use module_load_include as otherwise the above variables
    // will not be in the global scope in the included PHP file.
    require_once $memcache_php;
    drupal_exit();
  }
}

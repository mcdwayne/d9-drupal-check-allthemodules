<?php

/**
 * @file
 * Fto redirects.
 */

namespace Drupal\cleanpager\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Path\PathMatcher;

define('CLEANPAGER_ADDITIONAL_PATH_VARIABLE', 'page');

class CleanPagerSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    // Replace ?page=1 to /page/1.
    $events[KernelEvents::REQUEST][] = array('checkForRedirection');
    return $events;
  }

  public function checkForRedirection(GetResponseEvent $event) {
    global $_cleanpager_rewritten;
    $path = \Drupal::service('path.current')->getPath();
    if ($path_length = strpos($path, '/page/')) {
      $path_test_part = substr($path, 0, $path_length);
    }
    else {
      $path_test_part = $path;
    }
    $pages = \Drupal::configFactory()->get('cleanpager.settings')->get('cleanpager_pages');
    if (\Drupal::service('path.matcher')->matchPath($path_test_part, $pages)) {
      // Pass along additional query string values.
      $query_values = $_GET;
      if (isset ($query_values['page']) && !empty($query_values['page']) && $_cleanpager_rewritten == FALSE) {
        $path .= '/page/' . $query_values['page'];
        if (\Drupal::configFactory()->get('cleanpager.settings')->get('cleanpager_add_trailing')) {
          $path .= '/';
        }
        unset($query_values['page']);
        if (isset($query_values['q'])) {
          unset($query_values['q']);
        }
        $options['query'] = $query_values;
        $path .= (strpos($path, '?') !== FALSE ? '&' : '?') . $this->cleanPagerHttpBuildQuery($options['query']);
        unset($_GET['page']);
        header('Location: ' . $path, FALSE, 302);
        exit();
      }
    }
  }

  public function cleanPagerHttpBuildQuery(array $query, $parent = '') {
    $params = array();
    foreach ($query as $key => $value) {
      $key = ($parent ? $parent . '[' . rawurlencode($key) . ']' : rawurlencode($key));
      // Recurse into children.
      if (is_array($value)) {
        $params[] = $this->cleanPagerHttpBuildQuery($value, $key);
      }
      // If a query parameter value is NULL, only append its key.
      elseif (!isset($value)) {
        $params[] = $key;
      }
      else {
        // For better readability of paths in query strings, we decode slashes.
        $params[] = $key . '=' . str_replace('%2F', '/', rawurlencode($value));
      }
    }
    return implode('&', $params);
  }
}
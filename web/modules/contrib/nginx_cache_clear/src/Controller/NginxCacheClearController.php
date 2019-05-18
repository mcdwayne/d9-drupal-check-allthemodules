<?php

namespace Drupal\nginx_cache_clear\Controller;

use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller to clear the nginx cache.
 */
class NginxCacheClearController extends ControllerBase {
  /**
   * Function to clear the nginx cache file.
   *
   * The function helps to clear the cache of specific url(current) when user
   * click on the admin menu : Home -> Flush all caches -> Clear Nginc Cache.
   */
  public function nginxCacheClearFile() {
    $url = $_SERVER['HTTP_REFERER'];
    $alias_urls = array($url);
    $seperated_path = parse_url($url, PHP_URL_PATH);
    $alias = \Drupal::service('path.alias_manager')->getAliasByPath($seperated_path);

    if (strcmp($alias, $url) == 0) {
      // No alias was found.
      $alias = '';
    }
    $path .= $alias;

    if ($path) {
      if (preg_match('/node\/(\d+)/', $path, $matches)) {
        $node = Node::load($matches[1]);
      }
    }
    else {
      $node = NULL;
    }

    if (count(\Drupal::moduleHandler()->getImplementations('add_related_cached_url')) > 0) {
      foreach (\Drupal::moduleHandler()->getImplementations('add_related_cached_url') as $module) {
        $function = $module . '_add_related_cached_url';
        $function($alias_urls, $node);
      }
    }
    foreach ($alias_urls as $alias_url) {
      $msgs = array(
        "status" => t("The Nginx Cache of the @url is removed.", array("@url" => $alias_url)),
        "warning" => t("The Nginx Cache file of @url not found.", array("@url" => $alias_url)),
      );
      $status = $this->nginxClearUrl($alias_url) ? "status" : "warning";
      drupal_set_message($msgs[$status], $status);
    }
    $response = new RedirectResponse($path);
    $response->send();
    return '';
  }

  /**
   * Core function implementation nginxClearUrl($url)
   *
   * @params
   * String $url - [optional] Absolute url of the file to clear
   *
   * @return bool
   *   true/false
   *
   *   This function helps to delete the CGI/Proxy cache file in the server.
   *   This reads the configration settings in of the module and check is there
   *   file exist in that folder with the specific key format. If exist it will
   *   delete that cache file.
   */
  public function nginxClearUrl($url = '') {
    // Geting the cache manager configation.
    $server_cache_key = \Drupal::config('nginx_cache_clear.settings')->get('server_cache_key');
    $server_cache_path = \Drupal::config('nginx_cache_clear.settings')->get('server_cache_path');

    if ($url == '') {
      return FALSE;
    }

    $split_url = parse_url($url);
    $cache_keys = array();

    // Spliting the URL.
    $key_values = array(
      "scheme" => $split_url["scheme"],
      "host" => $split_url["host"],
      "request_uri" => $split_url["path"],
      "is_args" => isset($split_url["query"]),
      "args" => isset($split_url["query"]) ? $split_url["query"] : "",
    );
    $request_method = array("GET", "POST", "HEAD");

    $key_str = $server_cache_key;

    // Generating the key File.
    foreach ($key_values as $key => $value) {
      $key_str = str_replace("$$key", ($key == "is_args" || (!$key_values['is_args'] && $key == 'args')) ? "" : $value, $key_str);
    }

    if (strpos($key_str, '$request_method')) {
      foreach ($request_method as $value) {
        array_push($cache_keys, str_replace('$request_method', $value, $key_str));
      }
    }
    else {
      array_push($cache_keys, $key_str);
    }

    // Default Return value.
    $return = FALSE;

    foreach ($cache_keys as $value) {
      $value_md5 = md5($value);

      $cache_file = implode(DIRECTORY_SEPARATOR,
       array(
         $server_cache_path, substr($value_md5, -1),
         substr($value_md5, -3, 2),
         $value_md5,
       )
       );

      if (file_exists($cache_file)) {
        // Deleting the cache File.
        $return = $this->nginxCacheUnlink($cache_file);
      }
    }
    return $return;
  }

  /**
   * Deletes files and/or directories in the specified path.
   *
   * If the specified path is a directory the method will
   * call itself recursively to process the contents. Once the contents have
   * been removed the directory will also be removed.
   *
   * @param string $path
   *   A string containing either a file or directory path.
   *
   * @return bool
   *   TRUE for success or if path does not exist, FALSE in the event of an
   *   error.
   */
  public function nginxCacheUnlink($path) {
    if (file_exists($path)) {
      if (is_dir($path)) {
        // Ensure the folder is writable.
        @chmod($path, 0777);
        foreach (new \DirectoryIterator($path) as $fileinfo) {
          if (!$fileinfo->isDot()) {
            $this->unlink($fileinfo->getPathName());
          }
        }
        return @rmdir($path);
      }
      // Windows needs the file to be writable.
      @chmod($path, 0700);
      return @unlink($path);
    }
    // If there's nothing to delete return TRUE anyway.
    return TRUE;
  }

}

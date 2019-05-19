<?php

namespace Drupal\ipless\Asset;

use Drupal\Core\Asset\AssetResolver as AssetResolverDefault;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Description of AssetResolver.
 */
class AssetResolver extends AssetResolverDefault implements AssetResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function getLessAssets(AttachedAssetsInterface $assets) {
    $theme_info = $this->themeManager->getActiveTheme();

    // Add the theme name to the cache key since themes may implement
    // hook_library_info_alter().
    $libraries_to_load = $this->getLibrariesToLoad($assets);

    $cid = 'less:' . $theme_info->getName() . ':' . Crypt::hashBase64(serialize($libraries_to_load));

    if ($cached = $this->cache->get($cid)) {
      return $cached->data;
    }

    $less            = [];
    $default_options = [
      'type'       => 'file',
//      'group'      => LESS_AGGREGATE_DEFAULT,
      'weight'     => 0,
      'media'      => 'all',
      'preprocess' => TRUE,
      'browsers'   => [],
    ];

    foreach ($libraries_to_load as $library) {
      list($extension, $name) = explode('/', $library, 2);
      $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
      if (!isset($definition['less'])) {
        continue;
      }

      // @todo: add group sort (as $group => $data)
      foreach ($definition['less'] as $data) {
        foreach ($data as $file => $options) {

          if ($this->moduleHandler->moduleExists($extension)) {
            $extension_type = 'module';
          }
          else {
            $extension_type = 'theme';
          }

          $path     = drupal_get_path($extension_type, $extension);
          $pathinfo = pathinfo($file);

          $options['data'] = $path . '/' . $file;

          if (empty($options['less_path'])) {
            if ($pathinfo['dirname'] != '.')
              $options['less_path'] = '/' . $path . '/' . $pathinfo['dirname'];
            else
              $options['less_path'] = '/' . $path;
          }

          if (!$this->isValidUri($options['output'])) {
            $options['output'] = $path . '/' . $options['output'];
          }

          $options             += $default_options;
          $options['browsers'] += [
            'IE'  => TRUE,
            '!IE' => TRUE,
          ];

          // Files with a query string cannot be preprocessed.
          if ($options['type'] === 'file' && $options['preprocess']) {
            $options['preprocess'] = FALSE;
          }

          // Always add a tiny value to the weight, to conserve the insertion
          // order.
          $options['weight'] += count($less) / 1000;

          // LESS files are being keyed by the full path.
          $less[$options['data']] = $options;
        }
      }
    }

    // Allow modules and themes to alter the LESS assets.
    $this->moduleHandler->alter('less', $less, $assets);
    $this->themeManager->alter('less', $less, $assets);

    // Sort LESS items, so that they appear in the correct order.
//    uasort($less, 'static::sort');
    // Allow themes to remove LESS files by LESS files full path and file name.
    // @todo Remove in Drupal 9.0.x.
    if ($stylesheet_remove = $theme_info->getStyleSheetsRemove()) {
      foreach ($less as $key => $options) {
        if (isset($stylesheet_remove[$key])) {
          unset($less[$key]);
        }
      }
    }

    $this->cache->set($cid, $less, CacheBackendInterface::CACHE_PERMANENT, ['library_info']);

    return $less;
  }

  /**
   * 
   * @param string $uri
   */
  protected function isValidUri($uri) {
    return preg_match('/^(public|private|base):\/\//', $uri);
  }

}

<?php

namespace Drupal\text2image;

use Drupal\Core\Cache\Cache;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Text2ImageFonts.
 */
class Text2ImageFonts {

  protected $cachePrefix = 'text2image';

  /**
   * Constructs a new Text2ImageFonts object.
   */
  public function __construct() {

  }

  /**
   * Return an array of fonts.
   *
   * Scans through files available in the $path value (recursively).
   *
   * @return array
   *   Array of font paths => names.
   */
  public function getInstalledFonts($path, $refresh = FALSE) {
    if (!$refresh) {
      return $this->getFromCache('InstalledFonts.' . $path);
    }
    $filelist = [];
    $path = rtrim($path, '/') . '/';
    if (is_dir($path) && $handle = opendir($path)) {
      while ($file_name = readdir($handle)) {
        if ($file_name == '.' || $file_name == '..') {
          continue;
        }
        elseif (preg_match("/\.ttf$/i", $file_name) == 1) {
          $filelist[$path . $file_name] = basename($file_name, '.ttf');
        }
        elseif (is_dir($path . $file_name)) {
          $filelist += $this->getInstalledFonts($path . $file_name, TRUE);
        }
      }
      closedir($handle);
    }
    asort($filelist);
    $this->putInCache('InstalledFonts.' . $path, $filelist);
    return $filelist;
  }

  /**
   * Restore config settings to default values.
   */
  public function restoreDefaults() {
    $file = drupal_get_path('module', 'text2image') . '/config/install/text2image.settings.yml';
    $content = file_get_contents($file);
    $values = Yaml::parse($content);
    $config = \Drupal::configFactory()->getEditable('text2image.settings');
    $fonts = $this->getInstalledFonts($values['font_path'], TRUE);
    $filelist = [];
    foreach ($fonts as $file => $font) {
      $filelist[$file] = basename($file, '.ttf');
    }
    $config->set('fonts_selected', serialize($filelist));
    $config->set('font_path', $values['font_path']);
    $config->set('font_file', $values['font_file']);
    $config->set('font_size', $values['font_size']);
    $config->set('width', $values['width']);
    $config->set('height', $values['height']);
    $config->set('fg_color', $values['fg_color']);
    $config->set('bg_color', $values['bg_color']);
    $config->save();
  }

  /**
   * Clear text2image config settings.
   */
  public function clearConfig() {
    $config = \Drupal::configFactory()->getEditable('text2image.settings');
    $config->clear();
  }

  /**
   * Clear text2image cached items.
   *
   * @return bool
   *   TRUE on success.
   */
  public function clearCache() {
    $cid = $this->cachePrefix . $id;

    \Drupal::cache()->delete($cid);

    if (\Drupal::cache()->get($cid)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Retrieve an item from the cache.
   *
   * @param string $id
   *   Identifier to form part of prefix.
   *
   * @return mixed
   *   Return unserialized data or FALSE.
   */
  protected function getFromCache($id) {
    $cid = $this->cachePrefix . ':' . $id;

    if ($cache = \Drupal::cache()->get($cid)) {
      return $cache->data;
    }
    return FALSE;
  }

  /**
   * Store an item in the cache.
   *
   * @param string $id
   *   Identifier to form part of prefix.
   * @param mixed $data
   *   Data to cache.
   *
   * @return bool
   *   TRUE on success.
   */
  protected function putInCache($id, $data) {
    if ($id) {
      $cid = $this->cachePrefix . ':' . $id;

      \Drupal::cache()->set($cid, $data, Cache::PERMANENT, [$this->cachePrefix]);

      if (\Drupal::cache()->get($cid)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Delete an item from the cache.
   *
   * @param string $id
   *   Identifier to form part of prefix.
   *
   * @return bool
   *   TRUE on success.
   */
  protected function deleteFromCache($id) {
    $cid = $this->cachePrefix . $id;

    \Drupal::cache()->delete($cid);

    if (\Drupal::cache()->get($cid)) {
      return FALSE;
    }
    return TRUE;
  }

}

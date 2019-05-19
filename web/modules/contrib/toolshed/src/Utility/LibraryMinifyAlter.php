<?php

namespace Drupal\toolshed\Utility;

/**
 * A helper class to assist with switching between minified and expanded assets.
 *
 * CSS and JS can have minified and expanded versions. Typically a production
 * environment will want to use the minified versions, and for development it
 * is easier to use the expanded assets. This helper assists with switching
 * between the 2 versions if they are available.
 *
 * I recommend using the minified version in the a module.libraries.yml and
 * use LibrariesMinifiedAlter::minifiedToExpanded() to use the expanded versions
 * of the library files when a configuration indicates development resources
 * should be used.
 */
class LibraryMinifyAlter {

  /**
   * Searches a library definition for '.min' in the extension file extension.
   *
   * @param array $library
   *   Reference to a Drupal library definition, and alters it by removing
   *   all the asset definitions for files that have the '.min.js' or 'min.css'
   *   by removing the '.min' portion of the filename and set the minified flag.
   * @param string $extension
   *   The machine name of the extension this library belongs to.
   * @param string $type
   *   The type of extension that this library belongs to. Typically this would
   *   be either 'module' or 'theme'. Other extensions may support libraries,
   *   but at this time, only modules or themes are supported.
   */
  public static function minifiedToExpanded(array &$library, $extension, $type) {
    $path = drupal_get_path($type, $extension);

    // Alter CSS asset definitions, usually the files are at depth 2.
    if (!empty($library['css'])) {
      foreach ($library['css'] as $cssType => $assets) {
        foreach ($assets as $asset => $asset_def) {
          if (preg_match('#^(.+?/[\w\-_/]+)\.min\.css$#i', $asset, $matches)
            && (!isset($asset_def['type']) || $asset_def['type'] === 'file')
            && file_exists("{$path}/{$matches[1]}.css")
          ) {
            $library['css'][$cssType]["{$matches[1]}.css"] = ['minified' => FALSE] + $asset_def;
            unset($library['css'][$cssType][$asset]);
          }
        }
      }
    }

    // Check all JS assets for minified extension. Typically a depth of 1.
    if (!empty($library['js'])) {
      foreach ($library['js'] as $asset => $asset_def) {
        if (preg_match('#^(.+?/[\w\-_/]+)\.min\.js$#i', $asset, $matches)
          && (!isset($asset_def['type']) || $asset_def['type'] === 'file')
          && file_exists("{$path}/{$matches[1]}.js")
        ) {
          $library['js']["{$matches[1]}.js"] = ['minified' => FALSE] + $asset_def;
          unset($library['js'][$asset]);
        }
      }
    }
  }

  /**
   * Searches a library definition for assets not marked as minified.
   *
   * @param array $library
   *   Reference to a Drupal library definition, and alters it by removing
   *   all the asset definitions for files that aren't marked as minified and
   *   don't have the '.min' as part of the extension.
   * @param string $extension
   *   The machine name of the extension this library belongs to.
   * @param string $type
   *   The type of extension that this library belongs to. Typically this would
   *   be either 'module' or 'theme'. Other extensions may support libraries,
   *   but at this time, only modules or themes are supported.
   */
  public static function expandedToMinified(array &$library, $extension, $type) {
    $path = drupal_get_path($type, $extension);

    if (!empty($library['css'])) {
      foreach ($library['css'] as $cssType => $assets) {
        foreach ($assets as $asset => $asset_def) {
          if (preg_match('#^(.+?/[\w\-_/]+)\.(?<!min)css$#i', $asset, $matches)
            && (!isset($asset_def['type']) || $asset_def['type'] === 'file')
            && empty($asset_def['minified'])
            && file_exists("{$path}/{$matches[1]}.min.css")
          ) {
            $library['css'][$cssType]["{$matches[1]}.min.css"] = ['minified' => TRUE] + $asset_def;
            unset($library['css'][$cssType][$asset]);
          }
        }
      }
    }

    if (!empty($library['js'])) {
      foreach ($library['js'] as $asset => $asset_def) {
        if (preg_match('#^(.+?/[\w\-_/]+)\.(?<!min)js$#i', $asset, $matches)
          && (!isset($asset_def['type']) || $asset_def['type'] === 'file')
          && empty($asset_def['minified'])
          && file_exists("{$path}/{$matches[1]}.min.js")
        ) {
          $library['js']["{$matches[1]}.min.js"] = ['minified' => TRUE] + $asset_def;
          unset($library['js'][$asset]);
        }
      }
    }
  }

}

<?php
/**
 * @file
 * Contains \Drupal\widget_block\Utility\AssetsHelper.
 */

namespace Drupal\widget_block\Utility;

/**
 * Contains helper method for asset related operations.
 */
final class AssetsHelper {

    /**
     * Static class only.
     */
    private function __construct() {}

    /**
     * Get a list of Javascript assets.
     * 
     * @param string $unique_id
     *   A unique identifier which can be as prefix for resolved assets.
     * @param array $assets
     *   An associative array which contains the assets.
     *
     * @return array
     *   An array which first argument contains the header scripts and
     *   second argument contains the footer scripts.
     */
    public static function getJavascriptAssets($unique_id, array $assets) {
      // Initialize the $header_scripts and $footer_scripts to an empty array.
      $header_scripts = [];
      $footer_scripts = [];

      // Check whether Javascript assets are available.
      if (isset($assets['js']) && is_array($assets['js'])) {
        // Iterate through the Javascript assets.
        foreach ($assets['js'] as $asset) {
          // Check whether asset cannot be loaded from footer.
          if ($asset['defer'] === FALSE) {
            // Generate URL hash for given asset.
            $url_hash = sha1($asset['data']);
            // Build and append the Javascript tag entry.
            $header_scripts[] = [
              [
                '#tag' => 'script',
                '#attributes' => [
                  'type' => 'text/javascript',
                  'src' => $asset['data'],
                ],
              ],
              // Attach a unique identifier to ensure that duplicate entries across
              // the page only include this reference once.
              "{$unique_id}:js:{$url_hash}",
            ];
          }
          else {
            // Append the asset URL to the footer scripts list.
            $footer_scripts[] = $asset['data'];
          }
        }
      }

      return [$header_scripts, $footer_scripts];
    }

    /**
     * Get the stylesheet assets.
     *
     * @param string $unique_id
     *   A unique identifier which can be as prefix for resolved assets.
     * @param array $assets
     *   An associative array which contains the assets.
     *
     * @return array
     *   An array which contains the different stylesheet assets.
     */
    public static function getStylesheetAssets($unique_id, array $assets) {
      // Initialize $stylesheets variable to an empty array.
      $stylesheets = [];
      // Check whether stylesheet assets are available.
      if (isset($assets['css']) && is_array($assets['css'])) {
        // Iterate through the stylesheet assets.
        foreach ($assets['css'] as $asset) {
          // Generate URL hash for given asset.
          $url_hash = sha1($asset['data']);
          // Build and append the stylesheet tag entry.
          $stylesheets[] = [
            [
              '#tag' => 'link',
              '#attributes' => [
                'rel' => 'stylesheet',
                'href' => $asset['data'],
                'media' => 'all',
              ],
            ],
            // Attach a unique identifier to ensure that duplicate entries across
            // the page only include this reference once.
            "{$unique_id}:css:{$url_hash}",
          ];
        }
      }

      return $stylesheets;
    }

    /**
     * Get the assets grouped.
     *
     * @param string $unique_id
     *   A unique identifier which can be as prefix for resolved assets.
     * @param array $assets
     *   An associative array which contains the different assets.
     *
     * @return array
     *   An array which first argument contains the header assets and second
     *   argument contains the footer assets.
     */
    public static function getAssetsGrouped($unique_id, array $assets) {
      // Get the stylesheet assets.
      $css_header = static::getStylesheetAssets($unique_id, $assets);
      // Get the Javascript header and footer assets.
      list ($js_header, $js_footer) = static::getJavascriptAssets($unique_id, $assets);
      // Build the asset groups and merge type together.
      return [array_merge($css_header, $js_header), $js_footer];
    }

    /**
     * Apply assets to the specified render array.
     *
     * @param string $unique_id
     *   A unique identifier which can be as prefix for resolved assets.
     * @param array $assets
     *   An associative array which contains the different assets.
     * @param array $element
     *   The element to which the assets will be attached by reference.
     */
    public static function applyAssetsToRenderArray($unique_id, array $assets, array &$element) {
      // Get the assets by group.
      list ($html_head, $scripts) = static::getAssetsGrouped($unique_id, $assets);

      // Check whether $html_head is not empty.
      if (count($html_head) > 0) {
        // Attach the HTML head related assets.
        $element['#attached']['html_head'] = $html_head;
      }

      // Check whether $scripts is not empty.
      if (count($scripts) > 0) {
        // Check whether library should be initialized to an empty array.
        if (!isset($element['#attached']['library'])) {
          // Initialize library to an empty array.
          $element['#attached']['library'] = [];
        }

        // Check whether the script loader library should be added.
        if (!in_array('widget_block/script_loader', $element['#attached']['library'])) {
          // Include our custom script loader library.
          $element['#attached']['library'][] = 'widget_block/script_loader';
        }

        // Check whether the drupalSettings should be initialized.
        if (!isset($element['#attached']['drupalSettings'])) {
          // Initialize drupalSettings to an empty array.
          $element['#attached']['drupalSettings'] = [];
        }

        // Check whether the 'widget_block' setting should be initialized.
        if (!isset($element['#attached']['drupalSettings']['widget_block'])) {
          // Initialize the widget_block settings.
          $element['#attached']['drupalSettings']['widget_block'] = [];
        }

        // Check whether the 'script_loader' settings should be initialized.
        if (!isset($element['#attached']['drupalSettings']['widget_block']['script_loader'])) {
          // Initialize the script_loader settings.
          $element['#attached']['drupalSettings']['widget_block']['script_loader'] = [];
        }

        // Attach the required script for given ID.
        $element['#attached']['drupalSettings']['widget_block']['script_loader'][$unique_id] = $scripts;
      }
    }

}

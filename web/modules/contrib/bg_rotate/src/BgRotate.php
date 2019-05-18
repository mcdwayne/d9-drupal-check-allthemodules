<?php

namespace Drupal\bg_rotate;

use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Background rotate api.
 */
class BgRotate {

  private $config;
  private $fids;

  /**
   * Constructor.
   *
   * @property class config
   * @property array fids
   */
  public function __construct() {
    $this->config = \Drupal::config('bg_rotate.settings');
    $this->fids = \Drupal::state()->get('bg_rotate.images') ?: [];
  }

  /**
   * Loads images from file ids and build uris.
   *
   * @param array $fids
   *   Array of file ids.
   *
   * @return array
   *   Array of file uris.
   */
  private function buildUris(array $fids) {
    // Load files and get uris in an array.
    $images = File::loadMultiple($fids);
    $uris = [];
    $paths = [];
    foreach ($images as $image) {
      $uris[] = $image->getFileUri();
    }
    return $uris;
  }

  /**
   * Show on admin pages.
   *
   * @return bool
   */
  public function showAdmin() {
    return !empty($this->config->get('show_admin'));
  }

  /**
   * Builds the settings array.
   *
   * @return array
   *   Array to be sent to sent to js settings.
   */
  public function getSettings() {
    $config = $this->config;
    // Load images with image styles based on settings.
    $breakpoints = $config->get('breakpoints') ?: [];
    $backgrounds = [];
    // Build.
    $uris = $this->buildUris($this->fids);
    foreach ($uris as $uri) {
      $url = [];
      foreach ($breakpoints as $breakpoint) {
        $style = $breakpoint['image_style'];
        $width = $breakpoint['width'];
        if ($style == 'raw') {
          $url->{$width} = file_create_url($uri);
        }
        elseif ($style != 'none') {
          $url[$width] = ImageStyle::load($style)->buildUrl($uri);
        }
      }
      $backgrounds[] = $url;
    }

    return [
      // CSS selector.
      'selector' => $config->get('selector'),
      // Get interval.
      'interval' => $config->get('interval'),
      // URL set.
      'urls' => $backgrounds,
      // Background style.
      'css' => [
        'repeat' => $config->get('background_repeat'),
        'position' => $config->get('background_position'),
        'attachment' => $config->get('background_attachment'),
        'size' => $config->get('background_size'),
      ],
    ];
  }

}

<?php

namespace Drupal\udheader;

class Utils {

  /**
   * Get the images from the module directory.
   *
   * @return array
   *   List of the available images grouped by slot.
   */
  static function get_images() {
    $images = [
      'left' => [
        'about.jpg' => 'About',
        'cdcovers.jpg' => 'CD Covers',
        'cds.jpg' => 'CDs',
        'circle-tall.jpg' => 'Circle (Tall)',
        'community.jpg' => 'Community',
        'docs.jpg' => 'Docs',
        'news.jpg' => 'News',
        'partners.jpg' => 'Partners',
        'people.jpg' => 'People',
        'people-tall.jpg' => 'People (Tall)',
        'products.jpg' => 'Products',
        'student.jpg' => 'Student',
        'support.jpg' => 'Support',
      ],
      'center' => [
        'center.png' => 'Short',
        'center-tall.png' => 'Tall',
      ],
      'right' => [
        'plain.png' => 'Plain',
        'transparent.png' => 'Transparent',
        'orange.png' => 'Orange',
        'orange-logo.png' => 'Orange Logo',
        'orange-logo-tall.png' => 'Orange Logo (Tall)',
        'red.png' => 'Red',
        'red-tall.png' => 'Red (Tall)',
        'red-logo.png' => 'Red Logo',
        'red-logo-tall.png' => 'Red Logo (Tall)',
        'yellow.png' => 'Yellow',
        'yellow-logo.png' => 'Yellow Logo',
        'yellow-logo-tall.png' => 'Yellow Logo (Tall)',
      ],
    ];

    // Get left images
    $dir = drupal_get_path('module', 'udheader') . "/images/left/";
    $handle = opendir($dir);
    while (FALSE !== ($file = readdir($handle))) {
      if ($file != '.' && $file != '..') {
        if (!isset($images['left'][$file])) {
          $images['left'][$file] = $file;
        }
      }
    }
    closedir($handle);

    // Get center images
    $dir = drupal_get_path('module', 'udheader') . "/images/center/";
    $handle = opendir($dir);
    while (FALSE !== ($file = readdir($handle))) {
      if ($file != '.' && $file != '..') {
        if (!isset($images['center'][$file])) {
          $images['center'][$file] = $file;
        }
      }
    }
    closedir($handle);

    // Get right images
    $dir = drupal_get_path('module', 'udheader') . "/images/right/";
    $handle = opendir($dir);
    while (FALSE !== ($file = readdir($handle))) {
      if ($file != '.' && $file != '..') {
        if (!isset($images['right'][$file])) {
          $images['right'][$file] = $file;
        }
      }
    }
    closedir($handle);

    return $images;
  }
}

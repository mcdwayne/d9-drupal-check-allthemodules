<?php

namespace Drupal\filebrowser;

class Statistics {

  /**
   * @var array $listing Array containing the list of files to be displayed
   */
  protected $statistics;

  /**
   * Statistics constructor.
   * @param array $listing
   */
  public function __construct($listing) {
    $stats = $listing['data']['stats'];

    if ($stats['folders'] > 0) {
      $this->statistics['folders'] = \Drupal::translation()->formatPlural($stats['folders'], '1 folder', '@count folders');
    }
    if ($stats['files'] > 0) {
      $this->statistics['files'] = \Drupal::translation()->formatPlural($stats['files'], '1 file', '@count files');
      $this->statistics['size'] = format_size($stats['size']);
    }
  }

  public function get() {
    return [
      '#theme' => 'statistics',
      '#statistics' => $this->statistics,
    ];
  }

}
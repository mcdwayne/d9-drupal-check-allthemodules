<?php

namespace Drupal\bridtv;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service class to resolve resources (like images) hosted at Brid.TV.
 */
class BridResources {

  /**
   * The base url of snapshots (mainly images).
   *
   * @var string
   */
  protected $cdnSnapshotUrl;

  /**
   * BridResources constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $settings = $config_factory->get('bridtv.settings');
    $this->cdnSnapshotUrl = 'https://cdn.brid.tv/live/partners/' . $settings->get('partner_id') . '/snapshot/';
  }

  /**
   * Get the snapshot url for the given filename.
   *
   * @param string $filename
   *   The filename.
   *
   * @return string
   *   The snapshot url for the filename.
   */
  public function getSnaphotUrlFor($filename) {
    return $this->cdnSnapshotUrl . $filename;
  }

}

<?php

namespace Drupal\panels_extended\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Events called from our extended display builders.
 */
class ExtendedDisplayBuilderEvent extends Event {

  /**
   * Event called just before the regions are build.
   */
  const PREBUILD_REGIONS = 'panels_extended.builder.regions.prebuild';

  /**
   * A list of regions. Per region a list of blocks.
   *
   * @var array
   */
  private $regions;

  /**
   * Constructor.
   *
   * @param array $regions
   *   A list of regions. Per region a list of blocks.
   */
  public function __construct(array &$regions) {
    $this->regions = &$regions;
  }

  /**
   * Returns the regions.
   *
   * @return array
   *   The regions. Per region a list of blocks.
   */
  public function &getRegions() {
    return $this->regions;
  }

}

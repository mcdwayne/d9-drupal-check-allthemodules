<?php

namespace Drupal\adsense;

/**
 * Interface AdBlockInterface.
 */
interface AdBlockInterface {

  /**
   * Create ad object.
   *
   * @return AdsenseAdBase
   *   The created ad.
   */
  public function createAd();

}

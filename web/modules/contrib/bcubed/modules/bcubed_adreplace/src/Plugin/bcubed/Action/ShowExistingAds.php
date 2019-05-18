<?php

namespace Drupal\bcubed_adreplace\Plugin\bcubed\Action;

use Drupal\bcubed\ActionBase;

/**
 * Loads existing ads.
 *
 * @Action(
 *   id = "show_existing_ads",
 *   label = @Translation("Show Existing Ads"),
 *   description = @Translation("Shows the existing ads on the page")
 * )
 */
class ShowExistingAds extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'bcubed_adreplace/loadads';
  }

}

<?php

namespace Drupal\acsf\Event;

use Drupal\acsf\AcsfSite;

/**
 * This event handler populates the site information after the installation.
 */
class AcsfSiteInfoHandler extends AcsfEventHandler {

  /**
   * Implements AcsfEventHandler::handle().
   */
  public function handle() {
    $site = AcsfSite::load();
    $site->refresh();
  }

}

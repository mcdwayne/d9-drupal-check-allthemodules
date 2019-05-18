<?php

namespace Drupal\og_sm_path\Event;

/**
 * Defines events for site paths.
 */
final class SitePathEvents {

  /**
   * Act on the fact that the Site path changed for a Site node.
   *
   * Every Site node gets a Site path. When the value of that path changes this
   * event is triggered so other parts of the platform can respond to that
   * change. The event listener method receives a
   * \Drupal\og_sm_path\Event\SitePathEvent instance.
   *
   * @Event
   */
  const CHANGE = 'og_sm_path.change';

}

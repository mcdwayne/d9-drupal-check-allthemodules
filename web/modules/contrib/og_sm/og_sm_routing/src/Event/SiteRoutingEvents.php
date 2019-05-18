<?php

namespace Drupal\og_sm_routing\Event;

/**
 * Contains all events thrown in the og_sm_routing module.
 */
final class SiteRoutingEvents {

  /**
   * Event fired during route collection to allow site routes.
   *
   * This event is used to add new routes based upon sites. The event listener
   * method receives a \Drupal\og_sm_routing\Event\SiteRoutingEvent instance.
   *
   * @Event
   *
   * @var string
   */
  const COLLECT = 'og_sm_routing.site_route_collect';

  /**
   * Event fired during route collection to allow changing site routes.
   *
   * This event is used to alter existing site routes. The event listener
   * method receives a \Drupal\og_sm_routing\Event\SiteRoutingEvent instance.
   *
   * @Event
   *
   * @var string
   */
  const ALTER = 'og_sm_routing.site_route_alter';

}

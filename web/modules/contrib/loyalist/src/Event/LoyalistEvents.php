<?php

namespace Drupal\loyalist\Event;

/**
 * Class LoyalistEvents.
 *
 * @ingroup loyalist
 */
final class LoyalistEvents {

  /**
   * Name of the event fired when a new loyalist is identified.
   *
   * @Event
   *
   * @see \Drupal\loyalist\Event\LoyalistNewEvent
   *
   * @var string
   */
  const LOYALIST_NEW = 'loyalist.new';

  /**
   * Name of the event fired when an existing loyalist visits the site.
   *
   * @Event
   *
   * @see \Drupal\loyalist\Event\LoyalistVisitEvent
   *
   * @var string
   */
  const LOYALIST_VISIT = 'loyalist.visit';

}

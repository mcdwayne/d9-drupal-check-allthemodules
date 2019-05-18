<?php

namespace Drupal\og_sm_path\Event;

/**
 * Defines events for ajax paths.
 */
final class AjaxPathEvents {

  /**
   * Act when ajax paths are requested to be rewritten in site context.
   *
   * The event listener method receives a \Drupal\og_sm_path\Event\AjaxPathEvent
   * instance.
   *
   * @Event
   */
  const COLLECT = 'og_sm_path.ajax.collect';

}

<?php

namespace Drupal\external_entities\Event;

/**
 * Defines events for the external entities module.
 *
 * @see \Drupal\Core\Config\ConfigCrudEvent
 */
final class ExternalEntitiesEvents {

  /**
   * Name of the event fired when extracting raw data from an external entity.
   *
   * This event allows you to perform alterations on the raw data after
   * extraction.
   *
   * @Event
   */
  const EXTRACT_RAW_DATA = 'external_entity.extract_raw_data';

  /**
   * Name of the event fired when mapping raw data to an external entity.
   *
   * This event allows you to perform alterations on the external entity after
   * mapping.
   *
   * @Event
   */
  const MAP_RAW_DATA = 'external_entity.map_raw_data';

}

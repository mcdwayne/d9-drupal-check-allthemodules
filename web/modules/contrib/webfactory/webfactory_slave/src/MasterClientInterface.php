<?php

namespace Drupal\webfactory_slave;

/**
 * Define master client behavior.
 *
 * @package Drupal\webfactory_slave
 */
interface MasterClientInterface {

  /**
   * Retrieve all channel available for given satellite.
   *
   * @param string $satellite_id
   *   Satellite ID.
   *
   * @return array
   *   Channels available for given satellite.
   */
  public function getChannelsData($satellite_id);

  /**
   * Retrieves entities from master according to given channel id.
   *
   * You can specify an uuid to fetch only one entity.
   *
   * @param string $channel_id
   *   Channel ID.
   * @param string $uuid
   *   Entity uuid to fetch.
   * @param int $limit
   *   Limit number of the entities to retrieve.
   * @param int $offset
   *   Offset of the entities to get.
   *
   * @return array|mixed
   *   Return a list of entities or a full entity.
   */
  public function getEntitiesData($channel_id, $uuid = NULL, $limit = NULL, $offset = NULL);

}

<?php

namespace Drupal\bridtv;

/**
 * Helper class to build any known Brid.TV url endpoints.
 */
abstract class BridApiEndpoints {

  const BASE_URL = 'https://api.brid.tv/api/';

  /**
   * Returns the endpoint url to GET video information.
   *
   * @param int $id
   *   The video id.
   *
   * @return string
   *   The corresponding endpoint url.
   */
  static public function video($id) {
    return static::BASE_URL . 'video/' . $id . '.json';
  }

  /**
   * Returns the endpoint url to GET a paginated list of videos.
   *
   * @param int $id
   *   The site id, which can be the partner_id from settings.
   * @param int $page
   *   The page to navigate to. Default would be the first page.
   * @param int $limit
   *   The maximum number of videos to fetch. Default is 5 items.
   *
   * @return string
   *   The corresponding endpoint url.
   */
  static public function videosList($id, $page = 1, $limit = 5) {
    return static::BASE_URL . sprintf('videos/%d/page:%d/limit:%d.json', $id, $page, $limit);
  }

  /**
   * Returns the endpoint url to GET a list of players.
   *
   * @param int $id
   *   The site id, which can be the partner_id from settings.
   *
   * @return string
   *   The corresponding endpoint url.
   */
  static public function playersList($id) {
    return static::BASE_URL . 'playersList/' . $id . '.json';
  }

  /**
   * Returns the endpoint url to GET a list of players with its data.
   *
   * @param int $id
   *   The site id, which can be the partner_id from settings.
   *
   * @return string
   *   The corresponding endpoint url.
   */
  static public function playersDataList($id) {
    return static::BASE_URL . 'players/' . $id . '.json';
  }

}

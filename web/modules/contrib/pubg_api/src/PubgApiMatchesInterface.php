<?php

namespace Drupal\pubg_api;

/**
 * Pubg Api Matches Interface.
 *
 * @see https://documentation.playbattlegrounds.com/en/matches.html
 */
interface PubgApiMatchesInterface {

  /**
   * Get a collection of matches.
   *
   * @param string $shard
   *   A valid PUBG shard.
   * @param string $player_ids
   *   An optional comma separated list of players PUBG id.
   * @param string $game_mode
   *   'solo', 'duo', 'squad' @todo FPP?
   * @param string $filter_start
   *   Start date in ISO8601 format.
   *   Default and oldest value is now() - 14 days.
   * @param string $filter_end
   *   End date in ISO8601 format.
   *   Default and youngest value is now().
   * @param string $sort
   *   Either 'createdAt' (asc) or '-createdAt' (desc).
   * @param int $limit
   *   Optional limit used for pagination.
   *   If not specified, the server will default to a limit of 5.
   * @param int $offset
   *   Optional offset used for pagination.
   *   If not specified, the server will default to an offset of 0.
   *
   * @return array
   *   A collection of matches.
   */
  public function getMatches(string $shard, string $player_ids = '', string $game_mode = '', string $filter_start = '', string $filter_end = '', string $sort = 'createdAt', int $limit = 5, int $offset = 0);

  /**
   * Get a single match.
   *
   * @param string $shard
   *   A valid PUBG shard.
   * @param string $match_id
   *   The match id to search for.
   *
   * @return array
   *   The match infos.
   */
  public function getSingleMatch(string $shard, string $match_id);

}

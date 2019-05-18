<?php

namespace Drupal\pubg_api;

/**
 * Pubg Api Matches interface.
 *
 * @see https://documentation.playbattlegrounds.com/en/players.html
 */
interface PubgApiPlayersInterface {

  /**
   * Get a collection of players.
   *
   * @param string $shard
   *   A valid PUBG shard.
   * @param string $player_ids
   *   An optional comma separated list of players PUBG id.
   * @param string $player_names
   *   An optional comma separated list of players names.
   *
   * @return array
   *   A collection of players infos.
   */
  public function getPlayers(string $shard, string $player_ids = '', string $player_names = '');

  /**
   * Get a collection of players.
   *
   * @param string $shard
   *   A valid PUBG shard.
   * @param string $player_id
   *   A player PUBG id.
   *
   * @return array
   *   The player infos.
   */
  public function getSinglePlayer(string $shard, string $player_id);

}

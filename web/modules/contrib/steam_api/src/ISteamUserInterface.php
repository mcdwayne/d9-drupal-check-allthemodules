<?php

namespace Drupal\steam_api;

/**
 * ISteamUser interface.
 */
interface ISteamUserInterface {

  /**
   * Get User friends list.
   *
   * Returns the friend list of any Steam user,
   * provided their Steam Community profile visibility is set to "Public".
   *
   * @param string $steamcommunity_id
   *   The user steam community id.
   *
   * @return array
   *   The user's friends list, as an array of friends.
   *   Nothing will be returned if the profile is private.
   *
   * @see https://developer.valvesoftware.com/wiki/Steam_Web_API#GetFriendList_.28v0001.29
   */
  public function getFriendList(string $steamcommunity_id);

  /**
   * Get Community, VAC, and Economy ban statuses for given players.
   *
   * @param string $steamcommunity_ids
   *   Comma-delimited list of SteamIDs.
   *
   * @return array
   *   List of player ban objects for each 64 bit ID requested.
   *
   * @see https://developer.valvesoftware.com/wiki/Steam_Web_API#GetPlayerBans_.28v1.29
   */
  public function getPlayerBans(string $steamcommunity_ids);

  /**
   * Get player summaries.
   *
   * @param string $steamcommunity_ids
   *   Comma-delimited list of 64 bit IDs to return profile information for.
   *   Up to 100 Steam IDs can be requested.
   *
   * @return array
   *   Returns basic profile information for a list of 64-bit Steam IDs.
   *
   * @see https://developer.valvesoftware.com/wiki/Steam_Web_API#GetPlayerSummaries_.28v0002.29
   */
  public function getPlayerSummaries(string $steamcommunity_ids);

}

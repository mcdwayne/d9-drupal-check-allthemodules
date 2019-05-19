<?php

namespace Drupal\steam_api;

/**
 * ISteamUser interface.
 */
interface ISteamUserStatsInterface {

  /**
   * Get Global Achievement Percentages For App.
   *
   * @param string $app_id
   *   The ID for the game you're requesting.
   *
   * @return array
   *   A global achievements overview of a specific game in percentages.
   *
   * @see https://developer.valvesoftware.com/wiki/Steam_Web_API#GetGlobalAchievementPercentagesForApp_.28v0002.29
   */
  public function getGlobalAchievementPercentagesForApp(string $app_id);

  /**
   * Get Player Achievements.
   *
   * @param string $steamcommunity_id
   *   64 bit Steam ID to return achievements list for.
   * @param string $app_id
   *   The ID for the game you're requesting.
   * @param string $language
   *   If specified, it will return data in the requested language.
   *
   * @return array
   *   Player achievements.
   *
   * @see https://developer.valvesoftware.com/wiki/Steam_Web_API#GetPlayerAchievements_.28v0001.29
   */
  public function getPlayerAchievements(string $steamcommunity_id, string $app_id, string $language = '');

  /**
   * Get Schema For Game.
   *
   * @param string $app_id
   *   The ID for the game you're requesting.
   * @param string $language
   *   If specified, it will return data in the requested language.
   *
   * @return array
   *   Schema for the specified game.
   *
   * @see https://developer.valvesoftware.com/wiki/Steam_Web_API#getSchemaForGame_.28v0002.29
   */
  public function getSchemaForGame(string $app_id, string $language = '');

  /**
   * Get User Stats For Game.
   *
   * @param string $steamcommunity_id
   *   64 bit Steam ID to return stats list for.
   * @param string $app_id
   *   The ID for the game you're requesting.
   * @param string $language
   *   If specified, it will return data in the requested language.
   *
   * @return array
   *   Player stats.
   *
   * @see https://developer.valvesoftware.com/wiki/Steam_Web_API#GetUserStatsForGame_.28v0002.29
   */
  public function getUserStatsForGame(string $steamcommunity_id, string $app_id, string $language = '');

}

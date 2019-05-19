<?php

/**
 * @file
 * Definition of WoW\Guild\GuildServiceController.
 */

/**
 * Controller class for guilds.
 *
 * This extends the EntityServiceController class, adding required special
 * handling for guild objects.
 */
class WoWGuildServiceController extends WoWEntityServiceController {

  /**
   * The guild profile API is the primary way to access guild information.
   *
   * This guild profile API can be used to fetch a single guild at a time
   * through an HTTP GET request to a url describing the guild profile resource.
   * By default, a basic dataset will be returned and with each request and zero
   * or more additional fields can be retrieved. To access this API, craft a
   * resource URL pointing to the guild whos information is to be retrieved.
   *
   * @param WoWGuild $guild
   *   The wow_guild entity.
   * @param array $fields
   *   An array of fields to fetch:
   *   - members: A list of characters that are a member of the guild.
   *   - achievements: A set of data structures that describe the achievements
   *     earned by the guild.
   *   - news: A set of data structures that describe the news feed of the
   *   guild.
   *   - challenge: The top 3 challenge mode guild run times for each challenge
   *   mode map.
   *
   * @return WoWResponse
   *   The Response object returned by the service.
   *
   * @throws WoWException
   *   An exception in case of HTTP status 404 or 500.
   */
  public function fetch(WoWGuild $guild, array $fields = array()) {
    $response = wow_service($guild->region)
      ->newRequest("guild/$guild->realm/$guild->name")
        ->setQuery('fields', $fields)
        ->setLocale($guild->language)
        ->setIfModifiedSince($guild->lastModified)
        ->execute();

    return $this->handleResponse($guild, $response);
  }

  /**
   * (non-PHPdoc)
   * @see WoWEntityServiceController::merge()
   */
  public function merge($entity, WoWResponse $response) {
    $entity->merge($response->getArray());
    $entity->lastModified = $result->getArray('lastModified') / 1000;
  }
}

<?php

/**
 * @file
 * Definition of Drupal\wow_character\CharacterServiceController.
 */

namespace Drupal\wow_character;

use Drupal\wow\Entity\RemoteServiceController;

/**
 * Controller class for characters.
 *
 * This extends the EntityServiceController class, adding required special
 * handling for character objects.
 */
class CharacterServiceController extends RemoteServiceController {

  /**
   * The list of fields supported by the character resource end point.
   *
   * @var array
   */
  public static $fields = array(
    'achievements',
    'appearance',
    'feed',
    'guild',
    'hunterPets',
    'items',
    'mounts',
    'pets',
    'petSlots',
    'professions',
    'progression',
    'pvp',
    'quest',
    'reputation',
    'stats',
    'talents',
    'titles'
  );

  /**
   * Refreshes a Character entity.
   *
   * @param WoWCharacter $character
   *   The wow_character entity to refresh.
   *
   * @return WoWResponse
   *   The Response object returned by the service.
   *
   * @throws WoWException
   *   An exception in case of HTTP status 404, 500 or 503.
   */
  public function refresh(WoWCharacter $character) {
    $fields = array();
    // Fields are user defined: builds the 'fields' parameter with only what is
    // needed by the entity.
    foreach (self::$fields as $key) {
      if (isset($character->{$key})) {
        $fields[] = $key;
      }
    }

    $response = wow_service($character->region)
      ->newRequest("character/$character->realm/$character->name")
        ->setQuery('fields', $fields)
        ->setLocale($character->language)
        ->setIfModifiedSince($character->lastModified)
        ->execute();

    // Handles the response from the service.
    $this->handleResponse($character, $response);

    return $response;
  }

  /**
   * The Character API is the primary way to access character information.
   *
   * This Character Profile API can be used to fetch a single character at a
   * time through an HTTP GET request to a URL describing the character profile
   * resource.
   *
   * By default, a basic dataset will be returned and with each request and zero
   * or more additional fields can be retrieved. To access this API, craft a
   * resource URL pointing to the character whos information is to be retrieved.
   *
   * @param string $region
   *   The guild region.
   * @param string $realm
   *   The character realm.
   * @param string $name
   *   The character name.
   * @param array $fields
   *   An array of fields to fetch:
   *   - achievements: A map of achievement data including completion timestamps
   *     and criteria information.
   *   - appearance: A map of values that describes the face, features and
   *     helm/cloak display preferences and attributes.
   *   - feed: The activity feed of the character.
   *   - guild: A summary of the guild that the character belongs to. If the
   *     character does not belong to a guild and this field is requested, this
   *     field will not be exposed.
   *   - hunterPets: A list of all of the combat pets obtained by the character.
   *   - items: A list of items equipted by the character. Use of this field
   *     will also include the average item level and average item level
   *     equipped for the character.
   *   - mounts: A list of all of the mounts obtained by the character.
   *   - pets: A list of all of the combat pets obtained by the character.
   *   - petSlots: Data about the current battle pet slots on this characters
   *     account.
   *   - professions: A list of the character's professions. It is important to
   *     note that when this information is retrieved, it will also include the
   *     known recipes of each of the listed professions.
   *   - progression: A list of raids and bosses indicating raid progression and
   *     completedness.
   *   - pvp: A map of pvp information including arena team membership and rated
   *     battlegrounds information.
   *   - quests: A list of quests completed by the character.
   *   - reputation: A list of the factions that the character has an associated
   *     reputation with.
   *   - stats: A map of character attributes and stats.
   *   - talents: A list of talent structures.
   *   - titles: A list of the titles obtained by the character including the
   *     currently selected title.
   *
   * @return WoWCharacter
   *   The Character object returned by the service.
   *
   * @throws WoWException
   *   An exception in case of HTTP status 404, 500 or 503.
   */
  public function fetch($region, $realm, $name, array $fields = array()) {
    $values = array('region' => $region, 'realm' => $realm, 'name' => $name);
    $entities = $this->storage->load(FALSE, $values);
    // Creates the character entity if not found from the storage.
    $character = empty($entities) ? reset($entities) : $this->storage->create($values);

    $response = wow_service($character->region)
      ->newRequest("character/$character->realm/$character->name")
        ->setQuery('fields', $fields)
        ->setLocale($character->language)
        ->setIfModifiedSince($character->lastModified)
          ->execute();

    // Handles the response from the service.
    $this->handleResponse($character, $response);

    return $character;
  }

  /**
   * (non-PHPdoc)
   * @see WoWEntityServiceController::merge()
   */
  public function merge($entity, WoWResponse $response) {
    parent::merge($entity, $response);
    $entity->lastModified = $response->getData('lastModified') / 1000;
  }
}

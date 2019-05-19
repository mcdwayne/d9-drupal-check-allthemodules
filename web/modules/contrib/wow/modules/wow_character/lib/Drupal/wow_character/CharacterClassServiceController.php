<?php

/**
 * @file
 * Definition of Drupal\wow_character\CharacterClassServiceController.
 */

namespace Drupal\wow_character;

use Drupal\wow\Data\DataServiceController;

/**
 * Controller class for character classes.
 *
 * This extends the DataResourcesController class, adding required special
 * handling for character class objects.
 */
class CharacterClassServiceController extends DataServiceController {

	public function create(array $values = array()) {
		$entity = parent::create($values);
		$entity->wow_character_class = array(
				$values['language'] => array(
						0 => array('name' => $values['name'])));
		return $entity;
	}

  public function fetchAll($region, $language) {
    $response = wow_service($region)
      ->newRequest('data/character/classes')
        ->setLocale($language)
        ->onResponse()
          ->addMethodCallback(200, array($this, 'onResult'), $parameters)
          ->execute();

    $entities = array();
    $db_entities = $this->storage->load(FALSE);

    //
    if ($response->getCode() == 200) {
      // Merges the record returned by the API. This is a two-time merge where
      // updates and insert are performed first, then deletes the remaining
      // entities, which are not existing anymore service-side.
      foreach ($response->getData('classes') as $values) {
        $id = $values[$this->idKey];

        // Creates the entity from service values.
        $entities[$id] = $this->storage->create($values + array(
          'language' => $language,
          'is_new' => empty($db_entities[$id]),
        ));
        // Permanently saves the entity.
        $entities[$id]->save();
      }

      foreach (array_diff_key($db_entities, $entities) as $entity) {
        // Deletes extra entities that are not in the service anymore.
        $entity->delete();
      }

      // Updates the expires column of the wow_services table of this service.
      $result = db_select('wow_services', 's')
        ->fields('s', array('expires'))
        ->condition('language', $language)
        ->execute();

      $cache_control = explode('=', $response->getHeader('cache-control'));
    }
  }
}

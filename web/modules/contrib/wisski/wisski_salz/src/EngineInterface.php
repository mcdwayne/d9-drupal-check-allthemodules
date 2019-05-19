<?php

/**
 * @file
 * Contains Drupal\wisski_salz\EngineInterface.
 */

namespace Drupal\wisski_salz;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\wisski_salz\ExternalEntityInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines an interface for external entity storage client plugins.
 */
interface EngineInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface  {
  
  const SUCCESSFUL_WRITE = 1;
  const ERROR_ON_WRITE = 0;
  const NULL_WRITE = 2;
  const IS_READ_ONLY = 3;

  
  /**
   * returns the ID of the adapter that this engine instance belongs to
   * @return the adapter ID
   */
  public function adapterId();

  
  /**
   * determines whether an entity with this ID exists in the storage
   * @param $entity_id the ID of the given entity
   * @return TRUE if the storage handles this entity, FALSE otherwise
   */
  public function hasEntity($entity_id);

  
  /**
   * Returns Uris under which the instance given by $uri is known within this
   * adapter.
   * @param uri the URI that equivalent URIs should be searched for
   * @return an array of URIs
   */
  public function getSameUris($uri);


  /**
   * Returns Uris under which the instance given by $uri is known within this
   * adapter.
   * @param uri the URI that equivalent URIs should be searched for
   * @return an array of URIs
   */
  public function getSameUri($uri, $adapter_id);

  /**
   * saves a set of URI mappings with an optional drupal entity id.
   * @param $uris an associative array where the keys are adapter_ids and the values are uris which all mean the same individuum
   * the mapping denotes that the very adapter is holding information about that very URI
   * @param $entity_id the drupal ID for the entity that all the uris from $uris identify. If NULL we just save the uri identification without drupal ID matching
   */
  public function setSameUris($uris, $entity_id);
  

  /**
   * creates an individual URI that can be used in this very engine to
   * identitfy an individual
   */
  public function generateFreshIndividualUri();
  
  /**
   * returns an array of adapter-specific properties to be used in the setSameUris and getSameUris functions
   * of this adapter, these may be user-manipulated
   */
  public function getSameAsProperties();
  
  /**
   * returns an arrayof adapter-specific properties to be used in the setSameUris and getSameUris functions
   * of this adapter, these may be hard-coded an MUST NOT be user-manipulable
   */
  public function defaultSameAsProperties();

  
  /**
   * Creates a new entity and adds the new id to the given entity object
   * @param $entity the given entity
   * @return TRUE on success, false else.
   */
  public function createEntity($entity);
  
  /**
   * Deletes an existing entity
   * @param $entity the given entity
   * @return TRUE on success, false else.
   */
  public function deleteEntity($entity);


  /**
   * Loads all field data for multiple entities.
   *
   * If there is no entity with a given ID handled by this adapter i.e. we got no information about it
   * there MUST NOT be an entry with that ID in the result array.
   *
   * Note that this function gets passed Drupal entity IDs.
   * The engine is responsible for doing whatever ID handling/mapping/managing
   * is necessary to guarantee stable, persistent Drupal IDs if the storage
   * type does not use Drupal IDs.
   * 
   * @param $entity_ids an array of the entity IDs.
   * @param $field_ids an array with the machine names of the fields to search the values for
   * @param $language language code for the desired translation
   * @return an array describing the values TODO: describe structure
   */
  public function loadFieldValues(array $entity_ids = NULL, array $field_ids = NULL, $bundle = NULL,$language = LanguageInterface::LANGCODE_DEFAULT);

  
  /**
   * Loads property data for a given field for multiple entities.
   *
   * Note that this function gets passed Drupal entity IDs.
   * The engine is responsible for doing whatever ID handling/mapping/managing
   * is necessary to guarantee stable, persistent Drupal IDs if the storage
   * type does not use Drupal IDs.
   * 
   * retrieves the field data for the given entity IDs and field name
   * @param $field_id the machine name of the field to search the value for
   * @param $property_ids an array of specific sub-field property names e.g. value
   * @param $entity_ids an array of the entity IDs.
   * @param $language language code for the desired translation
   * @return an array describing the values TODO: describe structure
   */
  public function loadPropertyValuesForField($field_id, array $property_ids, array $entity_ids = NULL, $bundle = NULL,$language = LanguageInterface::LANGCODE_DEFAULT);

  /**
   * returns an instance of this Adapter's Query Class
   * @param $conjunction thetype of condition conjunction used i.e. AND or OR
   * @return \drupal\wisski_salz\WisskiQueryInterface
   */
  public function getQueryObject(EntityTypeInterface $entity_type, $condition,array $namespaces);
  
  /**
   * queries the bundles for a given entity id
   * @param $entity_id
   * @return an array of bundle-machine-names
   */
  public function getBundleIdsForEntityId($entity_id);
  
  /**
   * saves entity field information to the store permanently
   * in order to be loaded later-on
   * @param $entity_id the entity's drupal-internal ID
   * @param $field_values an array of field values keyed by the field_id. The second level arrays contain 
   * contain the main property name keyed 'main_property' and the numbered set of field items, each an array of 
   * field properties keyed by property name e.g.:
   * [
   *   'field_given_name' => [
   *		 'main_property' => 'value',
   *     0 => [
   *       'value' => 'Gotthold',
   *       'format' => 'basic_html',
   *     ],
   *     1 => [
   *       'value' => 'Ephraim',
   *       'format' => 'basic_html',
   *     ],
   *   ],
   *   'field_family_name' => [
   *		 'main_property' => 'value',
   *     0 => [
   *       'value' => 'Lessing',
   *       'format' => 'basic_html',
   *     ],
   *   ],
   * ]
   * @param $pathbuilder a pathbuilder object, that is connected to this adapter
   * @param $bundle the ID of the bundle the entities are in
   * @param $origignal_values an array of 'old' field values helping the adapter to decide which values to write, if necessary
   * @param $force_creation set to TRUE if the adapter shall store entity info even if it di not know the entity before
   * @param $initial_write set to TRUE if it is the first initial write of the entity after its creation. In this case we write everything without considering old values.
   * @TODO check how to include quantitive restrictions on field values
   * @return TRUE if the entity was successfully saved, FALSE or an error_string otherwise
   */
  public function writeFieldValues($entity_id,array $field_values,$pathbuilder,$bundle = NULL,$original_values=array(),$force_creation=FALSE,$initial_write=FALSE);

  /**
   * Checks if the engine knows something about the URI.
   */
  public function checkUriExists($uri);
  
  
  /**
   * Checks if the URI is in a valid syntax for this very adapter
   * @return TRUE if yes, FALSE otherwise
   */
  public function isValidUri($uri);
}

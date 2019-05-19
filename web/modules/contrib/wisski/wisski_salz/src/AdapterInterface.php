<?php

/**
 * @file
 * Contains \Drupal\wisski_salz\AdapterInterface.
 */

namespace Drupal\wisski_salz;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface for defining WissKI Salz Adapter entities.
 *
 * This interface also defines delegator methods for easy access of the basic
 * methods of the underlying engine
 */
interface AdapterInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {
  
  /**
   * @return string
   *  The human-readable description of the adapter instance set by the GUI
   */
  public function getDescription();

  /**
   * Sets the description
   *
   * @param description a string with the description
   */
  public function setDescription($description);


  /**
   * @return string
   *  The ID of the adapter's engine 
   */
  public function getEngineId();


  /**
   * Sets the engine ID for this adapter
   *
   * @param id the engine ID
   */
  public function setEngineId($id);


  /**
   * @return \Drupal\wisski_salz\EngineInterface
   *  The engine used by this adapter
   */
  public function getEngine();
  

  /**
   * Sets the configuration for the adapter's engine
   *
   * @param array the configuration
   */
  public function setEngineConfig(array $configuration);

  /**
   * @return \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   *  The plugin collection with the single engine
   */
  public function getEngineCollection();


  /**
   * @see EngineInterface::hasEntity()
   */
  public function hasEntity($entity_id);
  
  /**
   * @see EngineInterface::createEntity()
   */
  public function createEntity($entity);
  
  /**
   * @see EngineInterface::deleteEntity()
   */
  public function deleteEntity($entity);
  
  /**
   * @see EngineInterface::loadFieldValues()
   */
  public function loadFieldValues(array $entity_ids = NULL, array $field_ids = NULL, $bundle = NULL,$language = LanguageInterface::LANGCODE_DEFAULT);
  
  /**
   * @see EngineInterface::loadFieldValues()
   */
  public function loadPropertyValuesForField($field_id, array $property_ids, $entity_ids = NULL, $bundle = NULL,$language = LanguageInterface::LANGCODE_DEFAULT);

  /**
   * returns an instance of this Adapter's Query Class
   * @param $conjunction thetype of condition conjunction used i.e. AND or OR
   * @return \drupal\wisski_salz\WisskiQueryInterface
   */
  public function getQueryObject(EntityTypeInterface $entity_type, $condition,array $namespaces);
  
  /**
   * @see EngineInterface::getBundleIdsForEntityId
   */
  public function getBundleIdsForEntityId($entity_id);

  /**
   * @see EngineInterface::writeFieldValues
   */
  public function writeFieldValues($entity_id,array $field_values,$pathbuilder,$bundle = NULL,$original_values=array(),$force_creation=FALSE,$initial_write=FALSE);

  /**
   * this adapter/engine provides two functions for retrieving path alternatives
   * @TODO bring that to the interface
   */
  public function providesFastMode();

  /**
   * this adapter/engine provides a pre-computed step alternative cache
   * @TODO bring that to the interface
   */
  public function providesCacheMode();
  
  /**
   * returns TRUE if the cache is pre-computed and ready to use, FALSE otherwise
   */
  public function isCacheSet();
  
  /**
   * {@inheritdoc}
   * returns the possible next steps in path creation, if $this->providesFastMode() returns TRUE then this
   * MUST react fast i.e. in the blink of an eye if $fast_mode = TRUE and it MUST return the complete set of options if $fast_mode=FALSE
   * otherwise it should ignore the $fast_mode parameter
   */  
  public function getPathAlternatives($history = [], $future = [],$fast_mode=FALSE,$empty_uri='empty');
  
  public function getPrimitiveMapping($step);
  
  /*
   * Load the image data for a given entity id
   * @return an array of values?
   */
  public function getImagesForEntityId($entityid, $bundleid);
  
  public function getDrupalId($uri);
  
  public function getUriForDrupalId($id, $create);

  /**
   * Gets the bundle and loads every individual in the TS
   * and returns an array of ids if there is something...
   *
   */ 
  public function loadIndividualsForBundle($bundleid, $pathbuilder, $limit = NULL, $offset = NULL, $count = FALSE, $conditions = FALSE);
  
  public function loadEntity($id);
  
  public function loadMultipleEntities($ids = NULL);

}

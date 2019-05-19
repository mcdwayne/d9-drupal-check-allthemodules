<?php

/**
 * @file
 * Contains \Drupal\wisski_salz\Entity\Adapter.
 */

namespace Drupal\wisski_salz\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\wisski_salz\AdapterInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\wisski_salz\EngineCollection;
use Psr\Log\LoggerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the WissKI Salz Adapter entity.
 * 
 * @ConfigEntityType(
 *   id = "wisski_salz_adapter",
 *   label = @Translation("WissKI Salz Adapter"),
 *   handlers = {
 *     "list_builder" = "Drupal\wisski_salz\AdapterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\wisski_salz\Form\Adapter\AddForm",
 *       "edit" = "Drupal\wisski_salz\Form\Adapter\EditForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *   },
 *   config_prefix = "wisski_salz_adapter",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "description" = "description"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/wisski_salz/adapter/{wisski_salz_adapter}",
 *     "add-form" = "/admin/config/wisski_salz/adapter/add",
 *     "edit-form" = "/admin/config/wisski_salz/adapter/{wisski_salz_adapter}/edit",
 *     "delete-form" = "/admin/config/wisski_salz/adapter/{wisski_salz_adapter}/delete",
 *     "collection" = "/admin/config/wisski_salz/adapter"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "engine_id",
 *     "engine"
 *   }
 * )
 */
class Adapter extends ConfigEntityBase implements AdapterInterface {

  /**
   * The WissKI Salz Adapter ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The WissKI Salz Adapter label.
   *
   * @var string
   */
  protected $label;


  /**
   * A human-readable description of the adapter
   *
   * @var string
   */
  protected $description;


  /**
   * The engine id/type
   *
   * @var string
   */
  protected $engine_id;


  /**
   * An array with the engine configuration
   *
   * @var array
   */
  protected $engine = [];



  /**
   * The collection with the single engine
   *
   * @var EngineCollection
   */
  protected $engineCollection;

  
  
  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return array(
      'engine' => $this->getEngineCollection(),
    );
  }


  /** Returns the Engine Collection
   *
   * This is a convenience method.
   *
   * @return \Drupal\wisski_salz\EngineCollection
   */
  public function getEngineCollection() {
    if (!$this->engineCollection) {
      // DefaultSingleLazyPluginCollection expects the plugin instance id
      // to be identical to the plugin id.
      $this->engine['adapterId'] = $this->id();
      $this->engineCollection = new EngineCollection($this->getEngineManager(), $this->engine_id, $this->engine);
    }
    return $this->engineCollection;
  }


  /**
   * Returns the attribute manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The attribute manager.
   */
  public function getEngineManager() {
    return \Drupal::service('plugin.manager.wisski_salz_engine');
  }

  
  /**
   * {@inheritdoc}
   */
  public function getEngine() {
    return $this->getEngineCollection()->get($this->engine_id);
  }

  /**
   * {@inheritdoc}
   */
  public function setEngineConfig(array $configuration) {
    $this->engine = $configuration;
    $this->engine_id = $configuration['id'];
    $this->getEngineCollection()->setConfiguration($configuration);
  }
  
  public function setEngineId($id) {
    $this->engine_id = $id;
    $this->getEngineCollection()->addInstanceId($id);
  }

  public function getEngineId() {
    return $this->engine_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($d) {
    $this->description = trim($d);
  }

  /**
   * {@inheritdoc}
   */
  public function hasEntity($entity_id) {
    return $this->getEngine()->hasEntity($entity_id);
  }
  
  /**
   * {@inheritdoc}
   */
  public function createEntity($entity) {
    return $this->getEngine()->createEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteEntity($entity) {
    return $this->getEngine()->deleteEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function loadFieldValues(array $entity_ids = NULL, array $field_ids = NULL, $bundle = NULL,$language = LanguageInterface::LANGCODE_DEFAULT) {
    return $this->getEngine()->loadFieldValues($entity_ids, $field_ids, $bundle,$language);
  }

  /**
   * {@inheritdoc}
   */
  public function loadPropertyValuesForField($field_id, array $property_ids, $entity_ids = NULL, $bundle = NULL,$language = LanguageInterface::LANGCODE_DEFAULT) {
    return $this->getEngine()->loadPropertyValuesForField($field_id, $property_ids, $entity_ids, $bundle,$language);
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryObject(EntityTypeInterface $entity_type, $condition,array $namespaces) {
    return $this->getEngine()->getQueryObject($entity_type,$condition,$namespaces);
  }

  /**
   * {@inheritdoc}
   */  
  public function getBundleIdsForEntityId($entity_id) {
    return $this->getEngine()->getBundleIdsForEntityId($entity_id);
  }

  /**
   * {@inheritdoc}
   */  
  public function writeFieldValues($entity_id, array $field_values,$pathbuilder,$bundle = NULL,$original_values=array(),$force_creation=FALSE,$initial_write=FALSE) {
#    drupal_set_message("He called me!");
    return $this->getEngine()->writeFieldValues($entity_id,$field_values,$pathbuilder,$bundle,$original_values,$force_creation,$initial_write);
  }
  
  /**
   * this adapter/engine provides two functions for retrieving path alternatives
   * @TODO bring that to the interface
   */
  public function providesFastMode() {
    return $this->getEngine()->providesFastMode();
  }

  /**
   * this adapter/engine provides a pre-computed step alternative cache
   * @TODO bring that to the interface
   */
  public function providesCacheMode() {
    return $this->getEngine()->providesCacheMode();
  }
  
  /**
   * returns TRUE if the cache is pre-computed and ready to use, FALSE otherwise
   */
  public function isCacheSet() {
    return $this->getEngine()->isCacheSet();
  }
  
  /**
   * {@inheritdoc}
   * returns the possible next steps in path creation, if $this->providesFastMode() returns TRUE then this
   * MUST react fast i.e. in the blink of an eye if $fast_mode = TRUE and it MUST return the complete set of options if $fast_mode=FALSE
   * otherwise it should ignore the $fast_mode parameter
   */  
  public function getPathAlternatives($history = [], $future = [],$fast_mode=FALSE,$empty_uri='empty') {
    return $this->getEngine()->getPathAlternatives($history,$future,$fast_mode,$empty_uri);
  }
  
  public function getPrimitiveMapping($step) {
    return $this->getEngine()->getPrimitiveMapping($step);
  }
  
  /*
   * Load the image data for a given entity id
   * @return an array of values?
   */
  public function getImagesForEntityId($entityid, $bundleid) {
    return $this->getEngine()->getImagesForEntityId($entityid, $bundleid);
  }
  
  public function getDrupalId($uri) {
    return $this->getEngine()->getDrupalId($uri);
  }
  
  public function getUriForDrupalId($id, $create = TRUE) {
    return $this->getEngine()->getUriForDrupalId($id, $create);
  }

  /**
   * Gets the bundle and loads every individual in the TS
   * and returns an array of ids if there is something...
   *
   */ 
  public function loadIndividualsForBundle($bundleid, $pathbuilder, $limit = NULL, $offset = NULL, $count = FALSE, $conditions = FALSE) {
    return $this->getEngine()->loadIndividualsForBundle($bundleid, $pathbuilder, $limit, $offset,$count,$conditions);
  }
  
  public function loadEntity($id) {
    return $this->getEngine()->load($id);
  }
  
  public function loadMultipleEntities($ids = NULL) {
    return $this->getEngine()->loadMultiple($ids);
  }
  
  public function checkUriExists($uri) {
    return $this->getEngine()->checkUriExists($uri);
  }

  public function isWritable() {
    return $this->getEngine()->isWritable();
  }
  
}

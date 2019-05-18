<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 26.01.17
 * Time: 11:19
 */

namespace Drupal\elastic_search\Mapping;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\elastic_search\Entity\FieldableEntityMap;
use Drupal\elastic_search\Entity\FieldableEntityMapInterface;
use Drupal\elastic_search\Exception\CartographerMappingException;
use Drupal\elastic_search\Exception\CartographerRecursionException;
use Drupal\elastic_search\Plugin\FieldMapperInterface;
use Drupal\elastic_search\Plugin\FieldMapperManager;
use Drupal\elastic_search\ValueObject\IdDetails;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Cartographer
 *
 * @package Drupal\elastic_search\Mapping
 */
class Cartographer implements ContainerInjectionInterface {

  /**
   * @var FieldMapperManager
   */
  protected $definitionManager;

  /**
   * @var FieldableEntityMap[]
   */
  private $cachedMaps = [];

  /**
   * @var array
   */
  private $currentOptions = [];

  /**
   * @var EntityStorageInterface
   */
  private $storage;

  /**
   * @var array
   */
  private $mappedObjects = [];

  /**
   * @var int
   */
  private $currentDepth = 0;

  /**
   * Cartographer constructor.
   *
   * @param \Drupal\elastic_search\Plugin\FieldMapperManager $definitionManager
   * @param \Drupal\Core\Entity\EntityStorageInterface       $storage
   */
  public function __construct(FieldMapperManager $definitionManager, EntityStorageInterface $storage) {
    $this->definitionManager = $definitionManager;
    $this->storage = $storage;
  }

  /**
   * @inheritDoc
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.elastic_field_mapper_plugin'),
                      $container->get('entity_type.manager')->getStorage('fieldable_entity_map'));
  }

  /**
   * This function will render an elastic search compatible mapping array from a FieldableEntityMap.
   * THIS FUNCTION MUST NOT BE CALLED RECURSIVELY
   *
   * @param \Drupal\elastic_search\Entity\FieldableEntityMapInterface $entityMap
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\elastic_search\Exception\CartographerMappingException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function makeElasticMapping(FieldableEntityMapInterface $entityMap): array {

    //entity map must have an id
    if ($entityMap->id() === NULL) {
      throw new CartographerMappingException('FieldableEntityMap id is not set');
    }

    $this->initializeOptionsFromEntity($entityMap);

    if ($entityMap->hasDynamicMapping()) {
      // Deal with the dynamic map option
      // Essentially shortcuts the cartographer and lets elastic guess the mapping at index time
      return $this->buildOutputArray($entityMap->id(), []);
    }

    //PIterate the fields and build the properties
    $props = $this->iterateFields($entityMap->getFields());

    //return the built output array
    return $this->buildOutputArray($entityMap->id(), $props);

  }

  /**
   * Initialize the cartographer options from the entity map that we are traversing
   *
   * @param FieldableEntityMapInterface $entityMap
   */
  private function initializeOptionsFromEntity(FieldableEntityMapInterface $entityMap) {

    $this->mappedObjects = [$entityMap->id()];

    $this->currentOptions['child_only'] = $entityMap->isChildOnly();
    $this->currentOptions['recursion_depth'] = $entityMap->getRecursionDepth();

    $this->currentDepth = 0; //reset the current depth
  }

  /**
   * @param array[] $fields
   *
   * @param array   $id
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\elastic_search\Exception\CartographerMappingException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function iterateFields(array $fields, array $id = []): array {

    $props = [];
    foreach ($fields as $fid => $field) {
      $mergeId = array_merge($id, [$fid]);
      if ((int) $field['nested'] !== 0) {
        //dealing with a nested property means wrapping it
        $data = ['type' => 'nested'];
        NestedArray::setValue($props, $mergeId, $data);
      }
      $this->addProperties($field, $mergeId, $props);
    }
    return $props;
  }

  /**
   * @param array $fieldMap
   * @param array $id
   * @param array $props
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\elastic_search\Exception\CartographerMappingException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function addProperties(array $fieldMap,
                                 array $id,
                                 array &$props) {

    $map = array_shift($fieldMap['map']);

    if ($this->testTypeForNone($map['type'])) {
      return;
    }

    //Process the field. Objects are a special type as they need additional handling due to their nesting
    $this->testType($map['type']);
    if ($map['type'] === 'object') {
      $this->processObject($map, $id, $props);
    } else {
      //We need to set the options for the field here as well
      $details = $this->getDsl($map);
      NestedArray::setValue($props, $id, $details);

      //The rest of the field map after the first element is dealt with here
      //TODO - naming could be improved for this function as it actually builds extra mappings for this field
      $this->buildInternalFields($fieldMap['map'], array_merge($id, ['fields']), $props);
    }
  }

  /**
   * @param array $map
   * @param array $id
   * @param array $props
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\elastic_search\Exception\CartographerMappingException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function processObject(array $map,
                                 array $id,
                                 array &$props) {

    $id = array_key_exists('identifier', $map) ? array_merge($id, [$map['identifier']]) : $id;

    try {
      $mergeData = $this->getMappingObject($map);
      NestedArray::setValue($props,
                            array_merge($id, ['properties']),
                            $mergeData);

    } catch (CartographerRecursionException $e) {
      // If we had a recursion then we add a simple reference
      $details = $this->getDsl(['type' => 'simple_reference']);
      NestedArray::setValue($props, $id, $details);
      drupal_set_message('There was a mapping recursion with ' . $map['target_type'] .
                         '. This will not stop elasticsearch from working, but documents below this recursion point will not be flattened when indexed.',
                         'warning');
    }

  }

  /**
   * @param array $map
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function getDsl(array $map): array {
    /** @var FieldMapperInterface $plugin */
    $plugin = $this->definitionManager->createInstance($map['type']);
    $data = $plugin->getDslFromData($map['options'] ?? []);
    if (!array_key_exists('type', $data)) {
      $data['type'] = $map['type'];
    }
    return $data;
  }

  /**
   * @param array $map
   *
   * @return array|\Drupal\elastic_search\Entity\FieldableEntityMap
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\elastic_search\Exception\CartographerMappingException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\elastic_search\Exception\CartographerRecursionException
   */
  private function getMappingObject(array $map) {

    $bid = !empty($map['target_bundle']) ? $map['target_bundle'] :
      $map['target_type'];
    $idDetails = new IdDetails($map['target_type'], $bid);

    if ($this->currentDepth >= $this->currentOptions['recursion_depth']) {
      throw new CartographerRecursionException('recursion');
    }

    ++$this->currentDepth; //increment the recursion depth

    if (array_key_exists($idDetails->getId(), $this->cachedMaps)) {
      $mergeData = $this->cachedMaps[$idDetails->getId()];
    } else {
      /** @var FieldableEntityMap $mergeType */
      $mergeType = $this->getMergeMapEntity($idDetails);
      $mergeData = $this->iterateFields($mergeType->getFields());
      $this->cachedMaps[$idDetails->getId()] = $mergeData;
    }

    --$this->currentDepth; //decrement the recursion depth

    return $mergeData;
  }

  /**
   * @param \Drupal\elastic_search\ValueObject\IdDetails $idDetails
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *
   * @throws \Drupal\elastic_search\Exception\CartographerMappingException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function getMergeMapEntity(IdDetails $idDetails) {
    //if we are an object we can get its map and merge it in
    //$map will contain 'target_type' and 'target_bundle'
    //and we can use this to load our subtype map
    $entity = $this->storage->load($idDetails->getId());
    if (!$entity) {
      $t = new CartographerMappingException('Mapping for type ' . $idDetails->getId() . ' does not exist');
      $t->setId($idDetails);
      throw $t;
    }
    return $entity;
  }

  /**
   * @param string|array $map
   *
   * @return bool
   */
  private function testTypeForNone($map): bool {
    return ($map === 'none' || empty($map));
  }

  /**
   * @param array $fieldMap
   * @param array $id
   * @param array $props
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\elastic_search\Exception\CartographerMappingException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function buildInternalFields(array $fieldMap,
                                       array $id,
                                       array &$props) {
    foreach ($fieldMap as $key => $map) {

      $this->testType($map['type']);

      if ($map['type'] === 'object') {
        $this->processObject($map, $id, $props);
        continue;
      }

      $details = [];
      $details['type'] = $map['type'];
      if (array_key_exists('options', $map) && is_array($map['options'])) {
        /** @var FieldMapperInterface $plugin */
        $plugin = $this->definitionManager->createInstance($details['type']);
        $dsl = $plugin->getDslFromData($map['options']);
        /** @noinspection SlowArrayOperationsInLoopInspection */
        $details = array_merge($details, $dsl);
      }
      NestedArray::setValue($props,
                            array_merge($id, [$map['identifier']]),
                            $details);
    }
  }

  /**
   * @param string $id
   * @param array  $props
   *
   * @return array
   */
  private function buildOutputArray(string $id, array $props): array {
    return [
      'mappings' => [
        $id => [
          'properties' => $props,
        ],
      ],
    ];
  }

  /**
   * @param string $mapping
   *
   * @throws \Drupal\elastic_search\Exception\CartographerMappingException
   */
  private function testType(string $mapping) {
    if (!$this->definitionManager->hasDefinition($mapping)) {
      throw new CartographerMappingException('Field mapping type does not exist: ' .
                                             $mapping);
    }
  }

}
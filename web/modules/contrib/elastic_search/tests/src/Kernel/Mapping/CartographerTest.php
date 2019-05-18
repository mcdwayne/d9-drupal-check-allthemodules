<?php

namespace Drupal\Tests\elastic_search\Kernel\Mapping;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\elastic_search\Entity\FieldableEntityMap;
use Drupal\elastic_search\Mapping\Cartographer;
use Drupal\elastic_search\Plugin\FieldMapper\SimpleReference;
use Drupal\elastic_search\Plugin\FieldMapperInterface;
use Drupal\elastic_search\Plugin\FieldMapperManager;
use Drupal\KernelTests\KernelTestBase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * Class CartographerTest
 *
 * @group elastic_search
 */
class CartographerTest extends KernelTestBase {

  use MockeryPHPUnitIntegration;

  protected static $modules = [
    'elastic_search',
  ];

  /**
   * @param mixed $fmm
   *
   * @return \Drupal\elastic_search\Mapping\Cartographer
   */
  protected function getNewCartographer($fmm): Cartographer {
    return new Cartographer($fmm, $this->container->get('entity_type.manager')->getStorage('fieldable_entity_map'));
  }

  /**
   * @expectedException \Drupal\elastic_search\Exception\CartographerMappingException
   * @expectedExceptionMessage FieldableEntityMap id is not set
   */
  public function testNoId() {
    $cart = $this->getNewCartographer(\Mockery::mock(FieldMapperManager::class));

    $entityMap = FieldableEntityMap::create([]);

    $cart->makeElasticMapping($entityMap);
  }

  /**
   * Test when the mapping has an id but no fields.
   */
  public function testIdNoFields() {

    $fmm = \Mockery::mock(FieldMapperManager::class);
    $cart = $this->getNewCartographer($fmm);

    $entityMap = FieldableEntityMap::create([]);
    $mapId = 'test_map__bundle';
    $entityMap->setId($mapId);
    $mapping = $cart->makeElasticMapping($entityMap);

    $mappingsKey = 'mappings';
    $this->assertArrayHasKey($mappingsKey, $mapping);
    $this->assertArrayHasKey($mapId, $mapping[$mappingsKey]);
    $this->assertArrayHasKey('properties', $mapping[$mappingsKey][$mapId]);
  }

  /**
   * Test dynamic mapping option without fields
   */
  public function testDynamicMappingNoFields() {

    $fmm = \Mockery::mock(FieldMapperManager::class);
    $fmm->shouldReceive('hasDefinition')->andReturn(TRUE);
    //We also need to mock the methods to get the field instances
    $cart = $this->getNewCartographer($fmm);
    $entityMap = FieldableEntityMap::create([]);
    $mapId = 'test_map__bundle';
    $entityMap->setId($mapId);
    $entityMap->setDynamicMapping(TRUE);

    $mapping = $cart->makeElasticMapping($entityMap);

    $mappingsKey = 'mappings';
    $this->assertArrayHasKey($mappingsKey, $mapping);
    $this->assertArrayHasKey($mapId, $mapping[$mappingsKey]);
    $this->assertArrayHasKey('properties', $mapping[$mappingsKey][$mapId]);

  }

  /**
   * @expectedException \Drupal\elastic_search\Exception\CartographerMappingException
   * @expectedExceptionMessage Field mapping type does not exist:
   */
  public function testWithFieldsAndNoDefinition() {

    $fmm = \Mockery::mock(FieldMapperManager::class);
    $fmm->shouldReceive('hasDefinition')->andReturn(FALSE);
    $cart = $this->getNewCartographer($fmm);

    $entityMap = FieldableEntityMap::create([]);
    $mapId = 'test_map__bundle';
    $entityMap->setId($mapId);

    $entityMap->setFields(self::$realFieldData);

    $mapping = $cart->makeElasticMapping($entityMap);

  }

  /**
   * Test with fields and definitions
   */
  public function testWithFieldsAndDefinition() {

    $fmm = \Mockery::mock(FieldMapperManager::class);
    $fmm->shouldReceive('hasDefinition')->andReturn(TRUE);
    //We also need to mock the methods to get the field instances
    $fmi = \Mockery::mock(FieldMapperInterface::class);
    $fmi->shouldReceive('getDslFromData')->andReturn(self::$intDsl);
    $fmm->shouldReceive('createInstance')->andReturn($fmi);
    $cart = $this->getNewCartographer($fmm);

    $entityMap = FieldableEntityMap::create([]);
    $mapId = 'test_map__bundle';
    $entityMap->setId($mapId);

    $entityMap->setFields(self::$basicUserData);

    $mapping = $cart->makeElasticMapping($entityMap);
    $mappingsKey = 'mappings';
    $propertiesKey = 'properties';
    $fieldKey = array_keys(self::$basicUserData)[0];
    $this->assertArrayHasKey($mappingsKey, $mapping);
    $this->assertArrayHasKey($mapId, $mapping[$mappingsKey]);
    $this->assertArrayHasKey($propertiesKey, $mapping[$mappingsKey][$mapId]);
    $this->assertArrayHasKey($fieldKey, $mapping[$mappingsKey][$mapId][$propertiesKey]);
    $this->assertEquals(self::$intDsl, $mapping[$mappingsKey][$mapId][$propertiesKey][$fieldKey]);

  }

  /**
   * @expectedException \Drupal\elastic_search\Exception\CartographerMappingException
   * @expectedExceptionMessage Mapping for type user__user does not exist
   */
  public function testWithMissingMapping() {

    $fmm = \Mockery::mock(FieldMapperManager::class);
    $fmm->shouldReceive('hasDefinition')->andReturn(TRUE);
    //We also need to mock the methods to get the field instances
    $fmi = \Mockery::mock(FieldMapperInterface::class);
    $fmi->shouldReceive('getDslFromData')->andReturn([]);
    $fmm->shouldReceive('createInstance')->andReturn($fmi);
    $cart = $this->getNewCartographer($fmm);

    $entityMap = FieldableEntityMap::create([]);
    $mapId = 'test_map__bundle';
    $entityMap->setId($mapId);

    $entityMap->setFields(self::$realFieldData);

    $mapping = $cart->makeElasticMapping($entityMap);

  }

  /**
   * Recursion is a world of pain, as we can easily get in to an infinite mapping loop.
   * The below demonstrates this with 3 pieces of content which form a mapping loop
   */
  public function testSimplifiedRecursionExample() {

    $fmm = \Mockery::mock(FieldMapperManager::class);
    $fmm->shouldReceive('hasDefinition')->andReturn(TRUE);
    //We also need to mock the methods to get the field instances
    $fmi = \Mockery::mock(FieldMapperInterface::class);
    $fmi->shouldReceive('getDslFromData')->andReturn(SimpleReference::$simpleReferenceDsl);
    $fmm->shouldReceive('createInstance')->andReturn($fmi);

    //for this instance we don't use the container version of the mapper
    //because we want to return a bit of fake data that uses recursion
    $entityMap = FieldableEntityMap::create([]);
    $mapId = 'node__r1';
    $entityMap->setId($mapId);
    $entityMap->setFields(self::$r1);

    $entityMap2 = FieldableEntityMap::create([]);
    $mapId2 = 'node__r2';
    $entityMap2->setId($mapId2);
    $entityMap2->setFields(self::$r2);

    $entityMap3 = FieldableEntityMap::create([]);
    $mapId3 = 'node__r3';
    $entityMap3->setId($mapId3);
    $entityMap3->setFields(self::$r3);

    $esi = \Mockery::mock(EntityStorageInterface::class);
    //THE ORDER OF THESE CALLS MATTERS, what is a better way to do this?
    $esi->shouldReceive('load')->with('node__r2')->andReturn($entityMap2);
    $esi->shouldReceive('load')->with('node__r3')->andReturn($entityMap3);
    $esi->shouldReceive('load')->with('node__r1')->andReturn($entityMap);

    $cart = new Cartographer($fmm, $esi);

    //as long as this returns then this passed as it did not cause an infinite recursion
    $mapping = $cart->makeElasticMapping($entityMap);
    $this->assertTrue(TRUE);

  }

  /**
   * This demonstrates that with a 0 recursion setting the child object will be stored as a simple reference
   */
  public function testZeroRecursionDepth() {

    $fmm = \Mockery::mock(FieldMapperManager::class);
    $fmm->shouldReceive('hasDefinition')->andReturn(TRUE);
    //We also need to mock the methods to get the field instances
    $fmi = \Mockery::mock(FieldMapperInterface::class);
    $fmi->shouldReceive('getDslFromData')->andReturn(SimpleReference::$simpleReferenceDsl);
    $fmm->shouldReceive('createInstance')->andReturn($fmi);

    //for this instance we don't use the container version of the mapper
    //because we want to return a bit of fake data that uses recursion
    $entityMap = FieldableEntityMap::create([]);
    $mapId = 'node__r1';
    $entityMap->setId($mapId);
    $entityMap->setFields(self::$r1);
    $entityMap->setRecursionDepth(0);

    $entityMap2 = FieldableEntityMap::create([]);
    $mapId2 = 'node__r2';
    $entityMap2->setId($mapId2);
    $entityMap2->setFields(self::$r2);

    $entityMap3 = FieldableEntityMap::create([]);
    $mapId3 = 'node__r3';
    $entityMap3->setId($mapId3);
    $entityMap3->setFields(self::$r3);

    $esi = \Mockery::mock(EntityStorageInterface::class);
    //THE ORDER OF THESE CALLS MATTERS, what is a better way to do this?
    $esi->shouldReceive('load')->with('node__r2')->andReturn($entityMap2);
    $esi->shouldReceive('load')->with('node__r3')->andReturn($entityMap3);
    $esi->shouldReceive('load')->with('node__r1')->andReturn($entityMap);

    $cart = new Cartographer($fmm, $esi);

    //as long as this returns then this passed as it did not cause an infinite recursion
    $mapping = $cart->makeElasticMapping($entityMap);
    $this->assertArrayHasKey('field_r2_ref', $mapping['mappings']['node__r1']['properties']);
    $this->assertEquals('keyword', $mapping['mappings']['node__r1']['properties']['field_r2_ref']['type']);

  }

  /**
   * This demonstrates that with a 1 recursion setting the child object will be stored as a flattened mapping
   * And any mapping below this will be simplifed to a keyword reference
   */
  public function testSingleRecursionDepth() {

    $fmm = \Mockery::mock(FieldMapperManager::class);
    $fmm->shouldReceive('hasDefinition')->andReturn(TRUE);
    //We also need to mock the methods to get the field instances
    $fmi = \Mockery::mock(FieldMapperInterface::class);
    $fmi->shouldReceive('getDslFromData')->andReturn(SimpleReference::$simpleReferenceDsl);
    $fmm->shouldReceive('createInstance')->andReturn($fmi);

    //for this instance we don't use the container version of the mapper
    //because we want to return a bit of fake data that uses recursion
    $entityMap = FieldableEntityMap::create([]);
    $mapId = 'node__r1';
    $entityMap->setId($mapId);
    $entityMap->setFields(self::$r1);
    $entityMap->setRecursionDepth(1);

    $entityMap2 = FieldableEntityMap::create([]);
    $mapId2 = 'node__r2';
    $entityMap2->setId($mapId2);
    $entityMap2->setFields(self::$r2);

    $entityMap3 = FieldableEntityMap::create([]);
    $mapId3 = 'node__r3';
    $entityMap3->setId($mapId3);
    $entityMap3->setFields(self::$r3);

    $esi = \Mockery::mock(EntityStorageInterface::class);
    //THE ORDER OF THESE CALLS MATTERS, what is a better way to do this?
    $esi->shouldReceive('load')->with('node__r2')->andReturn($entityMap2);
    $esi->shouldReceive('load')->with('node__r3')->andReturn($entityMap3);
    $esi->shouldReceive('load')->with('node__r1')->andReturn($entityMap);

    $cart = new Cartographer($fmm, $esi);

    //as long as this returns then this passed as it did not cause an infinite recursion
    $mapping = $cart->makeElasticMapping($entityMap);
    $this->assertArrayHasKey('field_r2_ref', $mapping['mappings']['node__r1']['properties']);
    $this->assertArrayHasKey('field_r3_ref',
                             $mapping['mappings']['node__r1']['properties']['field_r2_ref']['properties']);
    $this->assertEquals('keyword',
                        $mapping['mappings']['node__r1']['properties']['field_r2_ref']['properties']['field_r3_ref']['type']);
  }

  /**
   * This demonstrates that simple references on the first level of an entity chain work, and maps for further into the chain are not necessary
   */
  public function testSimpleReferences() {

    $fmm = \Mockery::mock(FieldMapperManager::class);
    $fmm->shouldReceive('hasDefinition')->andReturn(TRUE);
    //We also need to mock the methods to get the field instances
    $fmi = \Mockery::mock(FieldMapperInterface::class);
    $fmi->shouldReceive('getDslFromData')->andReturn(SimpleReference::$simpleReferenceDsl);
    $fmm->shouldReceive('createInstance')->andReturn($fmi);

    //for this instance we don't use the container version of the mapper
    //because we want to return a bit of fake data that uses recursion
    $entityMap = FieldableEntityMap::create([]);
    $mapId = 'node__r1';
    $entityMap->setId($mapId);
    $entityMap->setFields(self::$r1_simple);
    $entityMap->setRecursionDepth(1);

    $esi = \Mockery::mock(EntityStorageInterface::class);

    $cart = new Cartographer($fmm, $esi);

    //as long as this returns then this passed as it did not cause an infinite recursion
    $mapping = $cart->makeElasticMapping($entityMap);
    $this->assertArrayHasKey('field_r2_ref', $mapping['mappings']['node__r1']['properties']);
    $this->assertEquals('keyword', $mapping['mappings']['node__r1']['properties']['field_r2_ref']['type']);
  }

  /**
   * Test that references in the second document are also respected and that as such a 3rd level map is not needed
   */
  public function testSimpleReferences2ndPlace() {

    $fmm = \Mockery::mock(FieldMapperManager::class);
    $fmm->shouldReceive('hasDefinition')->andReturn(TRUE);
    //We also need to mock the methods to get the field instances
    $fmi = \Mockery::mock(FieldMapperInterface::class);
    $fmi->shouldReceive('getDslFromData')->andReturn(SimpleReference::$simpleReferenceDsl);
    $fmm->shouldReceive('createInstance')->andReturn($fmi);

    //for this instance we don't use the container version of the mapper
    //because we want to return a bit of fake data that uses recursion
    $entityMap = FieldableEntityMap::create([]);
    $mapId = 'node__r1';
    $entityMap->setId($mapId);
    $entityMap->setFields(self::$r1);
    $entityMap->setRecursionDepth(200);

    $entityMap2 = FieldableEntityMap::create([]);
    $mapId2 = 'node__r2';
    $entityMap2->setId($mapId2);
    $entityMap2->setFields(self::$r2_simple);

    $esi = \Mockery::mock(EntityStorageInterface::class);
    //THE ORDER OF THESE CALLS MATTERS, what is a better way to do this?
    $esi->shouldReceive('load')->with('node__r2')->andReturn($entityMap2);
    $esi->shouldReceive('load')->with('node__r1')->andReturn($entityMap);

    $cart = new Cartographer($fmm, $esi);

    //as long as this returns then this passed as it did not cause an infinite recursion
    $mapping = $cart->makeElasticMapping($entityMap);
    $this->assertArrayHasKey('field_r2_ref', $mapping['mappings']['node__r1']['properties']);
    $this->assertArrayHasKey('field_r3_ref',
                             $mapping['mappings']['node__r1']['properties']['field_r2_ref']['properties']);
    $this->assertEquals('keyword',
                        $mapping['mappings']['node__r1']['properties']['field_r2_ref']['properties']['field_r3_ref']['type']);
  }

  public static $basicUserData = [
    'fid' =>
      [
        'map'    =>
          [
            0 =>
              [
                'type'    => 'integer',
                'options' =>
                  [
                    'boost'            => '0',
                    'doc_values'       => 1,
                    'index'            => 1,
                    'null_value'       => '',
                    'store'            => 0,
                    'coerce'           => 1,
                    'ignore_malformed' => 0,
                    'include_in_all'   => 1,
                  ],
              ],
          ],
        'nested' => '',
      ],
  ];

  public static $realFieldData = [
    'fid'      =>
      [
        'map'    =>
          [
            0 =>
              [
                'type'    => 'integer',
                'options' =>
                  [
                    'boost'            => '0',
                    'doc_values'       => 1,
                    'index'            => 1,
                    'null_value'       => '',
                    'store'            => 0,
                    'coerce'           => 1,
                    'ignore_malformed' => 0,
                    'include_in_all'   => 1,
                  ],
              ],
          ],
        'nested' => '',
      ],
    'uuid'     =>
      [
        'map'    =>
          [
            0 =>
              [
                'type'    => 'keyword',
                'options' =>
                  [
                    'boost'                 => '0',
                    'doc_values'            => 1,
                    'eager_global_ordinals' => 0,
                    'include_in_all'        => 1,
                    'index'                 => 1,
                    'index_options'         => 'docs',
                    'norms'                 => 0,
                    'null_value'            => '',
                    'store'                 => 0,
                    'similarity'            => 'classic',
                  ],
              ],
          ],
        'nested' => '',
      ],
    'langcode' =>
      [
        'map'    =>
          [
            0 =>
              [
                'type'    => 'keyword',
                'options' =>
                  [
                    'boost'                 => '0',
                    'doc_values'            => 1,
                    'eager_global_ordinals' => 0,
                    'include_in_all'        => 1,
                    'index'                 => 1,
                    'index_options'         => 'docs',
                    'norms'                 => 0,
                    'null_value'            => '',
                    'store'                 => 0,
                    'similarity'            => 'classic',
                  ],
              ],
          ],
        'nested' => '',
      ],
    'uid'      =>
      [
        'map'    =>
          [
            0 =>
              [
                'type'          => 'object',
                'options'       =>
                  [
                    'dynamic'        => 'true',
                    'enabled'        => 1,
                    'include_in_all' => 1,
                  ],
                'target_type'   => 'user',
                'target_bundle' => '',
              ],
          ],
        'nested' => '',
      ],
    'filename' =>
      [
        'map'    =>
          [
            0 =>
              [
                'type'    => 'keyword',
                'options' =>
                  [
                    'boost'                 => '0',
                    'doc_values'            => 1,
                    'eager_global_ordinals' => 0,
                    'include_in_all'        => 1,
                    'index'                 => 1,
                    'index_options'         => 'docs',
                    'norms'                 => 0,
                    'null_value'            => '',
                    'store'                 => 0,
                    'similarity'            => 'classic',
                  ],
              ],
          ],
        'nested' => '',
      ],
    'uri'      =>
      [
        'map'    =>
          [
            0 =>
              [
                'type'    => 'keyword',
                'options' =>
                  [
                    'boost'                 => '0',
                    'doc_values'            => 1,
                    'eager_global_ordinals' => 0,
                    'include_in_all'        => 1,
                    'index'                 => 1,
                    'index_options'         => 'docs',
                    'norms'                 => 0,
                    'null_value'            => '',
                    'store'                 => 0,
                    'similarity'            => 'classic',
                  ],
              ],
          ],
        'nested' => '',
      ],
    'filemime' =>
      [
        'map'    =>
          [
            0 =>
              [
                'type'    => 'keyword',
                'options' =>
                  [
                    'boost'                 => '0',
                    'doc_values'            => 1,
                    'eager_global_ordinals' => 0,
                    'include_in_all'        => 1,
                    'index'                 => 1,
                    'index_options'         => 'docs',
                    'norms'                 => 0,
                    'null_value'            => '',
                    'store'                 => 0,
                    'similarity'            => 'classic',
                  ],
              ],
          ],
        'nested' => '',
      ],
    'filesize' =>
      [
        'map'    =>
          [
            0 =>
              [
                'type'    => 'integer',
                'options' =>
                  [
                    'boost'            => '0',
                    'doc_values'       => 1,
                    'index'            => 1,
                    'null_value'       => '',
                    'store'            => 0,
                    'coerce'           => 1,
                    'ignore_malformed' => 0,
                    'include_in_all'   => 1,
                  ],
              ],
          ],
        'nested' => '',
      ],
    'status'   =>
      [
        'map'    =>
          [
            0 =>
              [
                'type'    => 'boolean',
                'options' =>
                  [
                    'boost'      => '0',
                    'doc_values' => 1,
                    'index'      => 1,
                    'null_value' => '',
                    'store'      => 0,
                  ],
              ],
          ],
        'nested' => '',
      ],
    'created'  =>
      [
        'map'    =>
          [
            0 =>
              [
                'type'    => 'date',
                'options' =>
                  [
                    'boost'            => '0',
                    'doc_values'       => 1,
                    'format'           =>
                      [
                        'epoch_millis'              => 'epoch_millis',
                        'strict_date_optional_time' => 'strict_date_optional_time',
                      ],
                    'locale'           => '',
                    'ignore_malformed' => 1,
                    'include_in_all'   => 1,
                    'index'            => 1,
                    'null_value'       => '',
                    'store'            => 0,
                  ],
              ],
          ],
        'nested' => '',
      ],
    'changed'  =>
      [
        'map'    =>
          [
            0 =>
              [
                'type'    => 'date',
                'options' =>
                  [
                    'boost'            => '0',
                    'doc_values'       => 1,
                    'format'           =>
                      [
                        'epoch_millis'              => 'epoch_millis',
                        'strict_date_optional_time' => 'strict_date_optional_time',
                      ],
                    'locale'           => '',
                    'ignore_malformed' => 1,
                    'include_in_all'   => 1,
                    'index'            => 1,
                    'null_value'       => '',
                    'store'            => 0,
                  ],
              ],
          ],
        'nested' => '',
      ],
  ];

  public static $intDsl = [
    'type'             => 'integer',
    'boost'            => 0,
    'coerce'           => TRUE,
    'doc_values'       => TRUE,
    'ignore_malformed' => FALSE,
    'include_in_all'   => TRUE,
    'index'            => TRUE,
    'store'            => FALSE,
  ];

  public static $r1 = [
    'field_r2_ref' =>
      [
        'map'    =>
          [
            0 =>
              [
                'type'          => 'object',
                'options'       =>
                  [
                    'dynamic'        => 'false',
                    'enabled'        => 1,
                    'include_in_all' => 0,
                  ],
                'target_type'   => 'node',
                'target_bundle' => 'r2',
              ],
          ],
        'nested' => '',
      ],
  ];
  public static $r1_simple = [
    'field_r2_ref' =>
      [
        'map'    =>
          [
            0 =>
              [
                'type' => 'simple_reference',
              ],
          ],
        'nested' => '',
      ],
  ];
  public static $r2 = [
    'field_r3_ref' =>
      [
        'map'    =>
          [
            0 =>
              [
                'type'          => 'object',
                'options'       =>
                  [
                    'dynamic'        => 'false',
                    'enabled'        => 1,
                    'include_in_all' => 0,
                  ],
                'target_type'   => 'node',
                'target_bundle' => 'r3',
              ],
          ],
        'nested' => '',
      ],
  ];
  public static $r2_simple = [
    'field_r3_ref' =>
      [
        'map'    =>
          [
            0 =>
              [
                'type' => 'simple_reference',
              ],
          ],
        'nested' => '',
      ],
  ];
  public static $r3 = [
    'field_r1_ref' =>
      [
        'map'    =>
          [
            0 =>
              [
                'type'          => 'object',
                'options'       =>
                  [
                    'dynamic'        => 'false',
                    'enabled'        => 1,
                    'include_in_all' => 0,
                  ],
                'target_type'   => 'node',
                'target_bundle' => 'r1',
              ],
          ],
        'nested' => '',
      ],
  ];

}

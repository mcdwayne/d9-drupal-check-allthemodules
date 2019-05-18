<?php

namespace Drupal\Tests\elastic_search\Unit\Plugin\FieldMapper;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\elastic_search\Elastic\ElasticDocumentManager;
use Drupal\elastic_search\Plugin\FieldMapper\RangeInteger;
use Drupal\Tests\UnitTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @group elastic_search
 */
class RangeIntegerTest extends UnitTestCase {

  use MockeryPHPUnitIntegration;

  /**
   * Test that analyzer dsl is built correctly
   */
  public function testPluginReturnsTheRightDsl() {
    $entityTypeManagerInterface = \Mockery::mock(EntityTypeManagerInterface::class);
    $elasticDocumentManager = \Mockery::mock(ElasticDocumentManager::class);

    $plugin = new RangeInteger([],
                               'plugin_id',
                               [],
                               $entityTypeManagerInterface,
                               $elasticDocumentManager);
    $dsl = $plugin->getDslFromData([]);

    $expected = [
      'type'       => 'nested',
      'properties' => [
        'from' => [
          'type' => 'integer',
        ],
        'to'   => [
          'type' => 'integer',
        ],
      ],
    ];
    $this->assertArrayEquals($expected, $dsl);
  }

  /**
   * @dataProvider fieldData
   */
  public function testPluginReturnsTheRightDataKeysOnly($data, $expected) {
    $entityTypeManagerInterface = \Mockery::mock(EntityTypeManagerInterface::class);
    $elasticDocumentManager = \Mockery::mock(ElasticDocumentManager::class);

    $plugin = new RangeInteger([],
                               'plugin_id',
                               [],
                               $entityTypeManagerInterface,
                               $elasticDocumentManager);

    $returnedData = $plugin->normalizeFieldData('', $data, []);
    $this->assertArrayEquals($expected, $returnedData);
  }

  /**
   * Test the support types.
   */
  public function testSupportedTypes() {
    $entityTypeManagerInterface = \Mockery::mock(EntityTypeManagerInterface::class);
    $elasticDocumentManager = \Mockery::mock(ElasticDocumentManager::class);

    $plugin = new RangeInteger([],
                               'plugin_id',
                               [],
                               $entityTypeManagerInterface,
                               $elasticDocumentManager);

    $supportedTypes = $plugin->getSupportedTypes();
    $expected = ['range_integer'];
    $this->assertArrayEquals($expected, $supportedTypes);
  }

  /**
   * Provides data.
   *
   * @return array
   */
  public function fieldData() {
    return [
      [
        [
          0   => [
            'from' => 142,
            'to'   => 1234,
          ],
          'x' => [
            'from' => 'a',
            'to'   => 'b',
          ],
          3   => [
            'nope' => [],
            'jess' => 12,
          ],
        ],
        [
          0 => [
            'from' => 142,
            'to'   => 1234,
          ],
          1 => [
            'from' => 'a',
            'to'   => 'b',
          ],
        ],
      ],
      [
        [
          0  => [
            'from'           => 11,
            'to'             => 7869038790,
            'something_else' => 8,
          ],
          18 => [
            'from'           => 0,
            'to'             => 1,
            'something_else' => 2,
          ],
        ],
        [
          0 => [
            'from' => 11,
            'to'   => 7869038790,
          ],
          1 => [
            'from' => 0,
            'to'   => 1,
          ],
        ],
      ],
      [
        [
          0 => [
            'from'           => -1,
            'to'             => ['a' => 'c'],
            'something_else' => 8,
          ],
        ],
        [
          0 => [
            'from' => -1,
            'to'   => ['a' => 'c'],
          ],
        ],
      ],
      [
        [],
        [],
      ],
    ];
  }

}

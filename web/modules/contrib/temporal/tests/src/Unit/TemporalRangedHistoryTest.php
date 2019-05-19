<?php
/**
 * @file
 * Contains \Drupal\Tests\temporal\Unit\TemporalRangedHistoryTest
 */

namespace Drupal\Tests\temporal\Unit;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\temporal\Entity\TemporalType;
use Drupal\temporal\TemporalListService;
use Drupal\temporal\TemporalRangedHistory;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the TemporalRangedHistory class.
 *
 * @package Drupal\Tests\temporal\Unit
 * @coversDefaultClass \Drupal\temporal\TemporalRangedHistory
 * @group temporal
 */
class TemporalRangedHistoryTest extends UnitTestCase {

  /**
   * @var Container
   */
  protected $container;

  /**
   * @var TemporalListService|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $list_service;

  /**
   * @var EntityTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity_type_manager;

  /**
   * @var EntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $temporal_type_storage;

  /**
   * @var EntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $node_storage;

  /**
   * @var TemporalRangedHistory
   */
  protected $ranged_history;

  public function setUp() {
    date_default_timezone_set('UTC');

    $node_changes = [];

    // Here's the data story:
    // Article node 1 is created in December 2015, with color 1 and shape 101.
    $node_changes[1]['article_color'] = [strtotime('2015-12-01') => 1];
    $node_changes[1]['article_shape'] = [strtotime('2015-12-01') => 101];

    // Article node 2 is created in January 2016, with color 1 and shape 101.
    $node_changes[2]['article_color'] = [strtotime('2016-01-04') => 1];
    $node_changes[2]['article_shape'] = [strtotime('2016-01-04') => 101];

    // In February 2016, article 2 gets color 2 and shape 102 assigned.
    $node_changes[2]['article_color'][strtotime('2016-02-04')] = 2;
    $node_changes[2]['article_shape'][strtotime('2016-02-04')] = 102;

    // Article node 3 is created in February 2016, with color 1 and shape 101.
    $node_changes[3]['article_color'][strtotime('2016-02-04')] = 1;
    $node_changes[3]['article_shape'][strtotime('2016-02-04')] = 101;

    // Article node 4 is created in February 2016 with color 1 and shape 102.
    $node_changes[4]['article_color'][strtotime('2016-02-04')] = 1;
    $node_changes[4]['article_shape'][strtotime('2016-02-04')] = 102;

    // Article node 4 changes mid-February.
    $node_changes[4]['article_color'][strtotime('2016-02-16')] = 2;
    $node_changes[4]['article_shape'][strtotime('2016-02-16')] = 101;

    // OK, mock it up.
    $this->list_service = $this->getMockBuilder('\Drupal\temporal\TemporalListService')
      ->disableOriginalConstructor()
      ->getMock();

    $this->temporal_type_storage = $this->getMockBuilder('\Drupal\Core\Config\Entity\ConfigEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $tag_type = new TemporalType([], 'temporal_type');
    $tag_type->setTemporalEntityType('node');
    $tag_type->setTemporalEntityField('field_article_color');

    $cat_type = new TemporalType([], 'temporal_type');
    $cat_type->setTemporalEntityType('node');
    $cat_type->setTemporalEntityField('field_article_shape');

    $this->temporal_type_storage->method('load')
      ->will($this->returnValueMap([
        ['article_color', $tag_type],
        ['article_shape', $cat_type],
      ]));

    $this->node_storage = $this->getMockBuilder('\Drupal\Core\Entity\EntityStorageBase')
      ->disableOriginalConstructor()
      ->getMock();

    $query = $this->getMockBuilder('Drupal\Core\Entity\Query\Sql\Query')
      ->disableOriginalConstructor()
      ->getMock();

    $node_ids = array_combine(array_keys($node_changes), array_keys($node_changes));
    $query->method('execute')->will($this->returnValue($node_ids));

    $this->node_storage->method('getQuery')
      ->will($this->returnValue($query));

    $this->entity_type_manager = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entity_type_manager->method('getStorage')
      ->will($this->returnValueMap([
        ['temporal_type', $this->temporal_type_storage],
        ['node', $this->node_storage],
      ]));

    // Set up the mock list service to provide data like the above.
    $i = 0;
    $this->list_service->method('prepareFieldValuesByTemporalType')
      ->will($this->returnArgument(0));
    $results_map = [];
    foreach (['article_color', 'article_shape'] as $temporal_type) {
      $expected_return[$temporal_type] = [];
      foreach ($node_changes as $node_id => $temporal_types) {
        foreach ($temporal_types[$temporal_type] as $created => $value) {
          $expected_return[$temporal_type][] = [
            'entity_id' => $node_id,
            'value' => $value,
            'created' => $created,
            'entity_type' => 'node',
            'entity_field' => 'field_' . $temporal_type,
          ];
        }
      }
      $results_map[] = [$temporal_type, $expected_return[$temporal_type]];
    }
    $this->list_service->method('getResults')
      ->will($this->returnValueMap($results_map));

    $this->ranged_history = new TemporalRangedHistory(
      $this->list_service,
      $this->entity_type_manager,
      ['article_color', 'article_shape'],
      strtotime('2016-01-01'),
      strtotime('2016-03-01'),
      new \DateInterval('P1M'),
      new \DateTimeZone('UTC')
    );
  }

  public function testGetUsedEntityFieldValues() {
    $color_result = $this->ranged_history->getUsedEntityFieldValues('article_color', [$this->ranged_history, 'reduceToClose']);
    $this->assertCount(2, $color_result);
    $this->assertContains(1, $color_result);
    $this->assertContains(2, $color_result);

    $shape_result = $this->ranged_history->getUsedEntityFieldValues('article_shape', [$this->ranged_history, 'reduceToClose']);
    $this->assertCount(2, $shape_result);
    $this->assertContains(101, $shape_result);
    $this->assertContains(102, $shape_result);
  }

  public function testGetFilteredEntityIDs() {
    $reducer = [$this->ranged_history, 'reduceToClose'];
    $filter  = function($value) { return $value == 2; };

    $result = $this->ranged_history->getFilteredEntityIDs('article_color', $reducer, $filter);

    $this->assertArrayEquals([
      'open' => [

      ],
      'close' => [
        2,
        4,
      ],
      strtotime('2016-01-01') => [

      ],
      strtotime('2016-02-01') => [
        2,
        4,
      ],
    ], $result);
  }

  public function testGetGroupedFilteredEntityIDs() {
    $result = $this->ranged_history->getGroupedFilteredEntityIDs(
      'article_color',
      [$this->ranged_history, 'reduceToClose'],
      function($value) { return $value == 1; },
      'article_shape',
      [$this->ranged_history, 'reduceToClose']
    );

    $this->assertArrayEquals([
      'open' => [
        101 => [
          1 => 1,
        ],
      ],
      strtotime('2016-01-01') => [
        101 => [
          1 => 1, 2 => 2,
        ],
      ],
      strtotime('2016-02-01') => [
        101 => [
          1 => 1, 3 => 3,
        ],
      ],
      'close' => [
        101 => [
          1 => 1, 3 => 3,
        ],
      ],
    ], $result);
  }

  public function testGetGroupedReducedPeriodValuesByEntityID() {
    $result = $this->ranged_history->getGroupedReducedPeriodValuesByEntityID(
      'article_color',
      [$this->ranged_history, 'reduceToClose'],
      function($value) { return $value == 1; },
      'article_shape',
      [$this->ranged_history, 'reduceToClose'],
      [$this->ranged_history, 'reduceToCount']
    );

    $this->assertArrayEquals([
      'open' => [
        101 => 1,
      ],
      strtotime('2016-01-01') => [
        101 => 2,
      ],
      strtotime('2016-02-01') => [
        101 => 2,
      ],
      'close' => [
        101 => 2,
      ],
    ], $result);
  }

  public function testGetReducedEntityFieldValues() {
    $result = $this->ranged_history->getReducedEntityFieldValues(
      'article_color',
      [$this->ranged_history, 'reduceToClose']
    );

    $this->assertArrayEquals([
      'open' => [
        1 => 1,
        2 => NULL,
        3 => NULL,
        4 => NULL,
      ],
      strtotime('2016-01-01') => [
        1 => 1,
        2 => 1,
        3 => NULL,
        4 => NULL,
      ],
      strtotime('2016-02-01') => [
        1 => 1,
        2 => 2,
        3 => 1,
        4 => 2,
      ],
      'close' => [
        1 => 1,
        2 => 2,
        3 => 1,
        4 => 2,
      ],
    ], $result);
  }

  public function testGetGroupedReducedEntityFieldValues() {
    $result = $this->ranged_history->getGroupedReducedEntityFieldValues(
      'article_color',
      [$this->ranged_history, 'reduceToClose'],
      'article_shape',
      [$this->ranged_history, 'reduceToClose']
    );

    $this->assertArrayEquals([
      'open' => [
        101 => [
          1 => 1,
        ],
      ],
      strtotime('2016-01-01') => [
        101 => [
          1 => 1,
          2 => 1,
        ],
      ],
      strtotime('2016-02-01') => [
        101 => [
          1 => 1,
          3 => 1,
          4 => 2,
        ],
        102 => [
          2 => 2,
        ],
      ],
      'close' => [
        101 => [
          1 => 1,
          3 => 1,
          4 => 2,
        ],
        102 => [
          2 => 2,
        ],
      ],
    ], $result);
  }

  public function testGetReducedPeriodValues() {
    $result = $this->ranged_history->getReducedPeriodValues(
      'article_color',
      [$this->ranged_history, 'reduceToClose'],
      [$this->ranged_history, 'reduceToSum']
    );

    $this->assertArrayEquals([
      'open' => 1,
      strtotime('2016-01-01') => 2,
      strtotime('2016-02-01') => 6,
      'close' => 6,
    ], $result);
  }

  public function tearDown() {
    unset($this->list_service);
  }

}

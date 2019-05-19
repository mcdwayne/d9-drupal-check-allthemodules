<?php

namespace Drupal\Tests\trail_graph\Unit\Plugin\TrailGraphData;

use Drupal\Tests\UnitTestCase;
use Drupal\trail_graph\Plugin\TrailGraphData\DefaultTrailGraphData;
use Drupal\trail_graph\Plugin\TrailGraphDataInterface;

/**
 * @coversDefaultClass \Drupal\trail_graph\Plugin\TrailGraphData\DefaultTrailGraphData
 *
 * @group trail_graph
 */
class DefaultTrailGraphDataTest extends UnitTestCase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityTypeManager = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeManager')->disableOriginalConstructor()->getMock();
    $this->connection = $this->getMockBuilder('\Drupal\Core\Database\Driver\mysql\Connection')->disableOriginalConstructor()->getMock();
  }

  /**
   * Tests constructor.
   *
   * @covers ::__construct
   */
  public function testConstruct() {
    $plugin = new DefaultTrailGraphData([], 'default_trail_graph_data', '', $this->entityTypeManager, $this->connection);
    $this->assertTrue($plugin instanceof TrailGraphDataInterface, "Plugin implements interface");
  }

  /**
   * Tests building form.
   *
   * @covers ::buildOptionsForm
   */
  public function testBuildOptionsForm() {
    $plugin = $this->getMockBuilder('Drupal\trail_graph\Plugin\TrailGraphData\DefaultTrailGraphData')->disableOriginalConstructor()->setMethods(['t'])->getMock();
    $plugin->expects($this->any())
      ->method('t')
      ->withAnyParameters()
      ->willReturn('String');
    $style = $this->getMockBuilder('\Drupal\views\Plugin\views\style\StylePluginBase')->disableOriginalConstructor()->getMockForAbstractClass();
    $displayHandler = $this->getMockBuilder('\stdClass')->setMethods(['getFieldLabels'])->getMock();

    $displayHandler->expects($this->once())
      ->method('getFieldLabels')
      ->withAnyParameters()
      ->willReturn([]);
    $style->displayHandler = $displayHandler;
    $style->options = ['trail_data_options' => []];

    $rendered_array = $plugin->buildOptionsForm($style);
    $this->assertArrayHasKey('title', $rendered_array, "title element is set.");
    $this->assertArrayHasKey('node_label', $rendered_array, "node_label element is set.");
    $this->assertArrayHasKey('content_preview', $rendered_array, "content_preview element is set.");
    $this->assertArrayHasKey('trail_field', $rendered_array, "trail_field element is set.");
    $this->assertArrayHasKey('trail_color', $rendered_array, "trail_color element is set.");
  }

  /**
   * Tests getting All Trail Data.
   *
   * @covers ::getAllTrailData
   */
  public function testGetAllTrailData() {
    $plugin = $this->getMockBuilder('Drupal\trail_graph\Plugin\TrailGraphData\DefaultTrailGraphData')
      ->disableOriginalConstructor()
      ->setMethods(['getRowNodes', 'getRowTrails'])
      ->getMock();
    $plugin->expects($this->once())
      ->method('getRowNodes')
      ->withAnyParameters()
      ->willReturn([]);
    $plugin->expects($this->once())
      ->method('getRowTrails')
      ->withAnyParameters()
      ->willReturn([]);
    $view = $this->getMockBuilder('\Drupal\views\ViewExecutable')->disableOriginalConstructor()->getMock();

    $rendered_array = $plugin->getAllTrailData($view);

    $this->assertArrayHasKey('trails', $rendered_array, "trails element is set.");
    $this->assertArrayHasKey('trail_nodes', $rendered_array, "trail_nodes element is set.");
  }

  /**
   * Tests getting row nodes.
   *
   * @covers ::getRowNodes
   */
  public function testGetRowNodes() {
    $plugin = new DefaultTrailGraphData([], 'default_trail_graph_data', '', $this->entityTypeManager, $this->connection);

    $obj = new \ReflectionClass('\Drupal\trail_graph\Plugin\TrailGraphData\DefaultTrailGraphData');
    $method = $obj->getMethod('getRowNodes');
    $method->setAccessible(TRUE);

    $row1 = new \stdClass();
    $row1->nid = 1;
    $row1->_entity = new \stdClass();

    $row2 = new \stdClass();
    $row2->nid = 2;
    $row2->_entity = new \stdClass();

    $rendered_array = $method->invokeArgs($plugin, [[$row1, $row2]]);

    $this->assertArrayHasKey(1, $rendered_array, "First element is set.");
    $this->assertArrayHasKey(2, $rendered_array, "Second element is set.");
  }

  /**
   * Tests getting row trails.
   *
   * @covers ::getRowTrails
   */
  public function testGetRowTrails() {
    $storage = $this->getMockBuilder('\Drupal\Core\Entity\EntityStorageInterface')->disableOriginalConstructor()->getMock();
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->with($this->identicalTo([2 => 2, 7 => 7]))
      ->willReturn(TRUE);
    $entityTypeManager = clone $this->entityTypeManager;
    $entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->withAnyParameters()
      ->willReturn($storage);

    $plugin = new DefaultTrailGraphData([], 'default_trail_graph_data', '', $entityTypeManager, $this->connection);

    $obj = new \ReflectionClass('\Drupal\trail_graph\Plugin\TrailGraphData\DefaultTrailGraphData');
    $method = $obj->getMethod('getRowTrails');
    $method->setAccessible(TRUE);

    $view = $this->getMockBuilder('\Drupal\views\ViewExecutable')->disableOriginalConstructor()->getMock();
    $view->style_plugin = new \stdClass();
    $view->style_plugin->options = ['trail_data_options' => ['trail_field' => 'field_trail']];

    $node1 = $this->getMockBuilder('\Drupal\node\Entity\Node')->disableOriginalConstructor()->getMock();
    $node1->expects($this->once())
      ->method('hasField')
      ->withAnyParameters()
      ->willReturn(TRUE);
    $field_definition = $this->getMockBuilder('\Drupal\Core\Field\FieldDefinitionInterface')->disableOriginalConstructor()->getMock();
    $field_definition->expects($this->once())
      ->method('getType')
      ->withAnyParameters()
      ->willReturn('entity_reference');
    $field_item_list = $this->getMockBuilder('\Drupal\Core\Field\EntityReferenceFieldItemListInterface')->disableOriginalConstructor()->getMock();
    $field_item_list->expects($this->once())
      ->method('getFieldDefinition')
      ->withAnyParameters()
      ->willReturn($field_definition);
    $field_item_list->expects($this->once())
      ->method('getValue')
      ->withAnyParameters()
      ->willReturn([['target_id' => 2], ['target_id' => 7]]);
    $node1->expects($this->once())
      ->method('get')
      ->withAnyParameters()
      ->willReturn($field_item_list);

    $method->invokeArgs($plugin, [[$node1], $view]);

  }

  /**
   * Tests getting trail nodes.
   *
   * @covers ::getTrailNodes
   */
  public function testGetTrailNodes() {
    $plugin = new DefaultTrailGraphData([], 'default_trail_graph_data', '', $this->entityTypeManager, $this->connection);

    $obj = new \ReflectionClass('\Drupal\trail_graph\Plugin\TrailGraphData\DefaultTrailGraphData');
    $method = $obj->getMethod('getTrailNodes');
    $method->setAccessible(TRUE);

    $rendered_array = $method->invokeArgs($plugin, [[], []]);

    $this->assertArrayEquals([], $rendered_array, "Empty array returned.");
  }

}

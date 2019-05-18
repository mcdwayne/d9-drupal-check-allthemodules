<?php

namespace Drupal\Tests\entity_pilot_map_config\Unit;

use Drupal\entity_pilot\AccountInterface;
use Drupal\entity_pilot\ArrivalInterface;
use Drupal\entity_pilot_map_config\ArrivalCreationResult;
use Drupal\entity_pilot_map_config\BundleMappingInterface;
use Drupal\entity_pilot_map_config\ConfigurationDifference;
use Drupal\entity_pilot_map_config\ConfigurationDifferenceManagerInterface;
use Drupal\entity_pilot_map_config\FieldMappingInterface;
use Drupal\entity_pilot_map_config\MappingManagerInterface;
use Drupal\entity_pilot_map_config\MatchingMappingsResult;
use Drupal\Tests\UnitTestCase;
use Drupal\entity_pilot_map_config\ArrivalCreationHandler;

/**
 * Tests arrival creation handler.
 *
 * @group entity_pilot
 *
 * @coversDefaultClass \Drupal\entity_pilot_map_config\ArrivalCreationHandler
 */
class ArrivalCreationHandlerTest extends UnitTestCase {

  /**
   * Difference manager mock.
   *
   * @var \Drupal\entity_pilot_map_config\ConfigurationDifferenceManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $differenceManager;

  /**
   * Mapping manager mock.
   *
   * @var \Drupal\entity_pilot_map_config\MappingManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $mappingManager;

  /**
   * Account mock.
   *
   * @var \Drupal\entity_pilot\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account;

  /**
   * Handler under test.
   *
   * @var \Drupal\entity_pilot_map_config\ArrivalCreationHandler
   */
  protected $handler;

  /**
   * Test arrival.
   *
   * @var \Drupal\entity_pilot\ArrivalInterface
   */
  protected $arrival;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->differenceManager = $this->createMock(ConfigurationDifferenceManagerInterface::class);
    $this->mappingManager = $this->createMock(MappingManagerInterface::class);
    $this->handler = new ArrivalCreationHandler($this->differenceManager, $this->mappingManager);
    $this->arrival = $this->createMock(ArrivalInterface::class);
    $this->account = $this->createMock(AccountInterface::class);
    $this->account->expects($this->any())
      ->method('id')
      ->willReturn(10);
    $this->arrival->expects($this->any())
      ->method('getAccount')
      ->willReturn($this->account);
  }

  /**
   * Tests buildNewArrivalResult with no difference.
   *
   * @covers ::buildNewArrivalResult
   */
  public function testBuildNewArrivalResultNoDifference() {
    // No difference.
    $this->differenceManager->expects($this->any())
      ->method('computeDifference')
      ->willReturn(new ConfigurationDifference([], [], []));
    $result = $this->handler->buildNewArrivalResult($this->arrival);
    $this->assertInstanceOf(ArrivalCreationResult::class, $result);
    $this->assertEquals(NULL, $result->getFieldMapping());
    $this->assertEquals(NULL, $result->getBundleMapping());
    $this->assertEquals([], $result->getDestinations());
  }

  /**
   * Tests buildNewArrivalResult with existing field difference.
   *
   * @covers ::buildNewArrivalResult
   */
  public function testBuildNewArrivalResultFieldDifferenceExists() {
    // Field difference.
    $this->differenceManager->expects($this->any())
      ->method('computeDifference')
      ->willReturn(new ConfigurationDifference([
        'node' => ['field_tags' => 'image'],
      ]));
    $field_mapping = $this->createMock(FieldMappingInterface::class);
    $this->mappingManager->expects($this->any())
      ->method('loadForConfigurationDifference')
      ->willReturn(new MatchingMappingsResult([], [$field_mapping]));
    $result = $this->handler->buildNewArrivalResult($this->arrival);
    $this->assertEquals($field_mapping, $result->getFieldMapping());
    $this->assertEquals(NULL, $result->getBundleMapping());
    $this->assertCount(0, $result->getDestinations());
  }

  /**
   * Tests buildNewArrivalResult with multiple existing field differences.
   *
   * @covers ::buildNewArrivalResult
   */
  public function testBuildNewArrivalResultFieldDifferenceMultiple() {
    // Field difference.
    $this->differenceManager->expects($this->any())
      ->method('computeDifference')
      ->willReturn(new ConfigurationDifference([
        'node' => ['field_tags' => 'image'],
      ]));
    $field_mapping = $this->createMock(FieldMappingInterface::class);
    $field_mapping_2 = $this->createMock(FieldMappingInterface::class);
    $this->mappingManager->expects($this->any())
      ->method('loadForConfigurationDifference')
      ->willReturn(new MatchingMappingsResult([], [$field_mapping, $field_mapping_2]));
    $result = $this->handler->buildNewArrivalResult($this->arrival);
    $this->assertEquals($field_mapping, $result->getFieldMapping());
    $this->assertEquals(NULL, $result->getBundleMapping());
    $destinations = $result->getDestinations();
    $this->assertCount(1, $destinations);
    $destination = reset($destinations);
    $this->assertEquals(ArrivalCreationHandler::ARRIVAL_MAPPING_SELECTION_ROUTE, $destination['route_name']);
  }

  /**
   * Tests buildNewArrivalResult with new field difference.
   *
   * @covers ::buildNewArrivalResult
   */
  public function testBuildNewArrivalResultFieldDifferenceNew() {
    // Field difference.
    $this->differenceManager->expects($this->any())
      ->method('computeDifference')
      ->willReturn(new ConfigurationDifference([
        'node' => ['field_tags' => 'image'],
      ]));
    $field_mapping = $this->createMock(FieldMappingInterface::class);
    $this->mappingManager->expects($this->any())
      ->method('loadForConfigurationDifference')
      ->willReturn(new MatchingMappingsResult([], []));
    $this->mappingManager->expects($this->any())
      ->method('createFieldMappingFromConfigurationDifference')
      ->willReturn($field_mapping);
    $result = $this->handler->buildNewArrivalResult($this->arrival);
    $this->assertEquals($field_mapping, $result->getFieldMapping());
    $this->assertEquals(NULL, $result->getBundleMapping());
    $destinations = $result->getDestinations();
    $this->assertCount(1, $destinations);
    $destination = reset($destinations);
    $this->assertEquals(ArrivalCreationHandler::FIELD_MAPPING_EDIT_ROUTE, $destination['route_name']);
  }

  /**
   * Tests buildNewArrivalResult with existing bundle difference.
   *
   * @covers ::buildNewArrivalResult
   */
  public function testBuildNewArrivalResultBundleDifferenceExists() {
    // Bundle difference.
    $this->differenceManager->expects($this->any())
      ->method('computeDifference')
      ->willReturn(new ConfigurationDifference([], ['node' => ['page']]));
    $bundle_mapping = $this->createMock(BundleMappingInterface::class);
    $this->mappingManager->expects($this->any())
      ->method('loadForConfigurationDifference')
      ->willReturn(new MatchingMappingsResult([$bundle_mapping], []));
    $result = $this->handler->buildNewArrivalResult($this->arrival);
    $this->assertEquals(NULL, $result->getFieldMapping());
    $this->assertEquals($bundle_mapping, $result->getBundleMapping());
    $this->assertCount(0, $result->getDestinations());
  }

  /**
   * Tests buildNewArrivalResult with multiple existing bundle differences.
   *
   * @covers ::buildNewArrivalResult
   */
  public function testBuildNewArrivalResultBundleDifferenceMultiple() {
    // Bundle difference.
    $this->differenceManager->expects($this->any())
      ->method('computeDifference')
      ->willReturn(new ConfigurationDifference([], ['node' => ['page']]));
    $bundle_mapping = $this->createMock(BundleMappingInterface::class);
    $bundle_mapping_2 = $this->createMock(BundleMappingInterface::class);
    $this->mappingManager->expects($this->any())
      ->method('loadForConfigurationDifference')
      ->willReturn(new MatchingMappingsResult([$bundle_mapping, $bundle_mapping_2], []));
    $result = $this->handler->buildNewArrivalResult($this->arrival);
    $this->assertEquals(NULL, $result->getFieldMapping());
    $this->assertEquals($bundle_mapping, $result->getBundleMapping());
    $destinations = $result->getDestinations();
    $this->assertCount(1, $destinations);
    $destination = reset($destinations);
    $this->assertEquals(ArrivalCreationHandler::ARRIVAL_MAPPING_SELECTION_ROUTE, $destination['route_name']);
  }

  /**
   * Tests buildNewArrivalResult with new bundle difference.
   *
   * @covers ::buildNewArrivalResult
   */
  public function testBuildNewArrivalResultBundleDifferenceNew() {
    // Bundle difference.
    $this->differenceManager->expects($this->any())
      ->method('computeDifference')
      ->willReturn(new ConfigurationDifference([], ['node' => ['page']]));
    $this->mappingManager->expects($this->any())
      ->method('loadForConfigurationDifference')
      ->willReturn(new MatchingMappingsResult([], []));
    $bundle_mapping = $this->createMock(BundleMappingInterface::class);
    $this->mappingManager->expects($this->any())
      ->method('createBundleMappingFromConfigurationDifference')
      ->willReturn($bundle_mapping);
    $result = $this->handler->buildNewArrivalResult($this->arrival);
    $this->assertEquals(NULL, $result->getFieldMapping());
    $this->assertEquals($bundle_mapping, $result->getBundleMapping());
    $destinations = $result->getDestinations();
    $this->assertCount(1, $destinations);
    $destination = reset($destinations);
    $this->assertEquals(ArrivalCreationHandler::BUNDLE_MAPPING_EDIT_ROUTE, $destination['route_name']);
  }

  /**
   * Tests buildNewArrivalResult with existing bundle and field difference.
   *
   * @covers ::buildNewArrivalResult
   */
  public function testBuildNewArrivalResultBundleAndFieldDifferenceExists() {
    // Field and bundle difference.
    $this->differenceManager->expects($this->any())
      ->method('computeDifference')
      ->willReturn(new ConfigurationDifference([
        'node' => ['field_tags' => 'image'],
      ], ['node' => ['page']]));
    $bundle_mapping = $this->createMock(BundleMappingInterface::class);
    $field_mapping = $this->createMock(FieldMappingInterface::class);
    $this->mappingManager->expects($this->any())
      ->method('loadForConfigurationDifference')
      ->willReturn(new MatchingMappingsResult([$bundle_mapping], [$field_mapping]));
    $result = $this->handler->buildNewArrivalResult($this->arrival);
    $this->assertEquals($field_mapping, $result->getFieldMapping());
    $this->assertEquals($bundle_mapping, $result->getBundleMapping());
    $this->assertCount(0, $result->getDestinations());
  }

  /**
   * Tests buildNewArrivalResult with new bundle and field difference.
   *
   * @covers ::buildNewArrivalResult
   */
  public function testBuildNewArrivalResultBundleAndFieldDifferenceNew() {
    // Field and bundle difference.
    $this->differenceManager->expects($this->any())
      ->method('computeDifference')
      ->willReturn(new ConfigurationDifference([
        'node' => ['field_tags' => 'image'],
      ], ['node' => ['page']]));
    $this->mappingManager->expects($this->any())
      ->method('loadForConfigurationDifference')
      ->willReturn(new MatchingMappingsResult([], []));
    $bundle_mapping = $this->createMock(BundleMappingInterface::class);
    $field_mapping = $this->createMock(FieldMappingInterface::class);
    $this->mappingManager->expects($this->any())
      ->method('createBundleMappingFromConfigurationDifference')
      ->willReturn($bundle_mapping);
    $this->mappingManager->expects($this->any())
      ->method('createFieldMappingFromConfigurationDifference')
      ->willReturn($field_mapping);
    $result = $this->handler->buildNewArrivalResult($this->arrival);
    $this->assertEquals($field_mapping, $result->getFieldMapping());
    $this->assertEquals($bundle_mapping, $result->getBundleMapping());
    $destinations = $result->getDestinations();
    $this->assertCount(2, $destinations);
    $this->assertEquals(ArrivalCreationHandler::BUNDLE_MAPPING_EDIT_ROUTE, $destinations[0]['route_name']);
    $this->assertEquals(ArrivalCreationHandler::FIELD_MAPPING_EDIT_ROUTE, $destinations[1]['route_name']);
  }

  /**
   * Tests buildNewArrivalResult with multiple bundles and new field difference.
   *
   * @covers ::buildNewArrivalResult
   */
  public function testBuildNewArrivalResultBundleMultipleAndFieldDifferenceNew() {
    // Field and bundle difference.
    $this->differenceManager->expects($this->any())
      ->method('computeDifference')
      ->willReturn(new ConfigurationDifference([
        'node' => ['field_tags' => 'image'],
      ], ['node' => ['page']]));
    $bundle_mapping = $this->createMock(BundleMappingInterface::class);
    $bundle_mapping_2 = $this->createMock(BundleMappingInterface::class);
    $this->mappingManager->expects($this->any())
      ->method('loadForConfigurationDifference')
      ->willReturn(new MatchingMappingsResult([$bundle_mapping, $bundle_mapping_2], []));
    $field_mapping = $this->createMock(FieldMappingInterface::class);
    $this->mappingManager->expects($this->any())
      ->method('createFieldMappingFromConfigurationDifference')
      ->willReturn($field_mapping);
    $result = $this->handler->buildNewArrivalResult($this->arrival);
    $this->assertEquals($field_mapping, $result->getFieldMapping());
    $this->assertEquals($bundle_mapping, $result->getBundleMapping());
    $destinations = $result->getDestinations();
    $this->assertCount(2, $destinations);
    $this->assertEquals(ArrivalCreationHandler::FIELD_MAPPING_EDIT_ROUTE, $destinations[0]['route_name']);
    $this->assertEquals(ArrivalCreationHandler::ARRIVAL_MAPPING_SELECTION_ROUTE, $destinations[1]['route_name']);
  }

}

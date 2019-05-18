<?php

namespace Drupal\Tests\healthz\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\healthz\Plugin\HealthzCheck\SearchApiSolr;
use Drupal\search_api\Backend\BackendInterface;
use Drupal\search_api\ServerInterface;

/**
 * Unit tests for the SearchApiSolr plugin.
 *
 * @coversDefaultClass \Drupal\healthz\Plugin\HealthzCheck\SearchApiSolr
 *
 * @group healthz
 */
class SearchApiSolrCheckUnitTest extends HealthzUnitTestBase {

  /**
   * The mock module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $moduleHandler;

  /**
   * The mock search api server entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $serverStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->serverStorage = $this->prophesize(EntityStorageInterface::class);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('search_api_server')->willReturn($this->serverStorage->reveal());

    $this->plugin = new SearchApiSolr(['settings' => ['search_api_server' => 'test_server']], 'test', ['provider' => 'test'], $this->moduleHandler->reveal(), $entity_type_manager->reveal());
  }

  /**
   * Test the applies function.
   */
  public function testApplies() {
    $this->moduleHandler->moduleExists('search_api_solr')->willReturn(TRUE);
    $this->assertTrue($this->plugin->applies());
    $this->moduleHandler->moduleExists('search_api_solr')->willReturn(FALSE);
    $this->assertFalse($this->plugin->applies());
  }

  /**
   * Tests the check function.
   */
  public function testCheck() {
    $this->serverStorage->load('test_server')->willReturn(NULL);
    $this->assertFalse($this->plugin->check());
    $this->assertCount(1, $this->plugin->getErrors());
    $server = $this->prophesize(ServerInterface::class);
    $backend = $this->prophesize(BackendInterface::class);
    $backend->isAvailable()->willReturn(FALSE);
    $server->getBackend()->willReturn($backend->reveal());
    $this->serverStorage->load('test_server')->willReturn($server->reveal());
    $this->assertFalse($this->plugin->check());
    $this->assertCount(2, $this->plugin->getErrors());
    $backend->isAvailable()->willReturn(TRUE);
    $this->assertTrue($this->plugin->check());
  }

}

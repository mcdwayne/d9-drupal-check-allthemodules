<?php

namespace Drupal\Tests\radioactivity\Unit;

use Drupal\Core\State\StateInterface;
use Drupal\radioactivity\DefaultIncidentStorage;
use Drupal\radioactivity\Incident;
use Drupal\radioactivity\IncidentStorageInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\radioactivity\DefaultIncidentStorage
 * @group radioactivity
 */
class DefaultIncidentStorageTest extends UnitTestCase {

  /**
   * A mock state storage.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The system under test.
   *
   * @var \Drupal\radioactivity\DefaultIncidentStorage
   */
  protected $sut;

  /**
   * A mock radioactivity incident.
   *
   * @var \Drupal\radioactivity\Incident
   */
  protected $incident;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->state = $this->prophesize(StateInterface::class);
    $this->sut = new DefaultIncidentStorage($this->state->reveal());
    $this->incident = $this->prophesize(Incident::class);
  }

  /**
   * @covers ::addIncident
   */
  public function testAddIncident() {
    $this->sut->addIncident($this->incident->reveal());

    $this->state->set(IncidentStorageInterface::STORAGE_KEY, Argument::any())->shouldBeCalled();
  }

  /**
   * @covers ::getIncidents
   */
  public function testGetIncidents() {
    $content = $this->incident->reveal();
    $this->state->get(IncidentStorageInterface::STORAGE_KEY, Argument::any())->willReturn([$content]);

    $result = $this->sut->getIncidents();

    $this->assertEquals($result[0], $content);
  }

  /**
   * @covers ::getIncidentsByType
   */
  public function testGetIncidentsByType() {
    $incident = $this->incident->reveal();
    $this->incident->getEntityTypeId()->willReturn('type1', 'type1', 'type2');
    $this->incident->getEntityId()->willReturn('123', '234', '345');
    $this->state->get(IncidentStorageInterface::STORAGE_KEY, Argument::any())->willReturn([
      $incident,
      $incident,
      $incident,
    ]);

    $result = $this->sut->getIncidentsByType('type1');

    $this->assertEquals(count($result), 2);
    $this->assertEquals($result[234], [$incident]);

    $this->incident->getEntityTypeId()->willReturn('type1', 'type1', 'type2');
    $this->incident->getEntityId()->willReturn('123', '234', '345');
    $result = $this->sut->getIncidentsByType();

    $this->assertEquals(count($result), 2);
    $this->assertEquals(count($result['type2']), 1);
    $this->assertEquals($result['type2']['345'], [$incident]);
  }

  /**
   * @covers ::clearIncidents
   */
  public function testClearIncidents() {
    $this->sut->clearIncidents();
    $this->state->set(IncidentStorageInterface::STORAGE_KEY, Argument::any())->shouldBeCalled();
  }

  /**
   * @covers ::injectSettings
   */
  public function testInjectSettings() {
    $page = [];
    $this->sut->injectSettings($page);
    $this->assertTrue(isset($page['#attached']['drupalSettings']['radioactivity']));
  }

}

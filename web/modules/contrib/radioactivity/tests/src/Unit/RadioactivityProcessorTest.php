<?php

namespace Drupal\Tests\radioactivity\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\State\StateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\radioactivity\IncidentStorageInterface;
use Drupal\radioactivity\RadioactivityProcessor;
use Drupal\radioactivity\RadioactivityProcessorInterface;
use Drupal\radioactivity\StorageFactory;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\radioactivity\RadioactivityProcessor
 * @group radioactivity
 */
class RadioactivityProcessorTest extends UnitTestCase {

  /**
   * The radioactivity processor under test.
   *
   * @var \Drupal\radioactivity\RadioactivityProcessorInterface
   */
  protected $sut;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Mock field storage configuration.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fieldStorageConfig;

  /**
   * Mock state system.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Mock logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Mock Radioactivity logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * @var \Drupal\radioactivity\StorageFactory
   */
  protected $storage;

  /**
   * @var \Drupal\Radioactivity\IncidentStorageInterface
   */
  protected $incidentStorage;

  /**
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Dummy request timestamp.
   *
   * @var int
   */
  protected $requestTime = 1000;

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManager::class);
    $this->fieldStorageConfig = $this->prophesize(EntityStorageInterface::class);
    $this->entityTypeManager->getStorage('field_storage_config')
      ->willReturn($this->fieldStorageConfig->reveal());

    $this->state = $this->prophesize(StateInterface::class);
    $loggerFactory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $this->loggerChannel = $this->prophesize(LoggerChannelInterface::class);
    $loggerFactory->get(RadioactivityProcessorInterface::LOGGER_CHANNEL)
      ->willReturn($this->loggerChannel->reveal());

    $this->storage = $this->prophesize(StorageFactory::class);

    $this->incidentStorage = $this->prophesize(IncidentStorageInterface::class);
    $this->storage->getConfiguredStorage()->willReturn($this->incidentStorage->reveal());

    $time = $this->prophesize(TimeInterface::class);
    $time->getRequestTime()->willReturn($this->requestTime);

    $this->queueFactory = $this->prophesize(QueueFactory::class);

    $this->sut = new RadioactivityProcessor($this->entityTypeManager->reveal(), $this->state->reveal(), $loggerFactory->reveal(), $this->storage->reveal(), $time->reveal(), $this->queueFactory->reveal());
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
//    $this->verifyMockObjects();
  }

  /**
   * @covers ::processDecay
   */
  public function testProcessDecayNoFields() {

    $data = [];
    $this->fieldStorageConfig->loadByProperties(['type' => 'radioactivity'])
      ->willReturn($data);

    $this->state->set(RadioactivityProcessorInterface::LAST_PROCESSED_STATE_KEY, Argument::any())
      ->shouldNotBeCalled();
    $this->loggerChannel->notice(Argument::any())->shouldNotBeCalled();

    $this->sut->processDecay();
  }

  /**
   * @covers ::processDecay
   */
  public function testProcessDecayNoData() {

    $profile = 'count';
    $hasData = FALSE;
    $resultCount = 0;

    $configData = $this->prophesize(FieldStorageConfig::class);
    $configData->getSetting('profile')->willReturn($profile);
    $configData->hasData()->willReturn($hasData);

    $data = [$configData->reveal()];
    $this->fieldStorageConfig->loadByProperties(['type' => 'radioactivity'])
      ->willReturn($data);

    $this->state->set(RadioactivityProcessorInterface::LAST_PROCESSED_STATE_KEY, Argument::any())
      ->shouldNotBeCalled();
    $this->loggerChannel->notice('Processed @count radioactivity decays.', ['@count' => $resultCount])
      ->shouldBeCalled();

    $this->sut->processDecay();
  }

  /**
   * @covers ::processDecay
   */
  public function testProcessDecayCountProfile() {

    $profile = 'count';
    $hasData = TRUE;
    $resultCount = 0;

    $configData1 = $this->prophesize(FieldStorageConfig::class);
    $configData1->getSetting('profile')->willReturn($profile);
    $configData1->hasData()->willReturn($hasData);

    $data = [$configData1->reveal()];
    $this->fieldStorageConfig->loadByProperties(['type' => 'radioactivity'])
      ->willReturn($data);

    $this->state->set(RadioactivityProcessorInterface::LAST_PROCESSED_STATE_KEY, Argument::any())
      ->shouldNotBeCalled();
    $this->loggerChannel->notice('Processed @count radioactivity decays.', ['@count' => $resultCount])
      ->shouldBeCalled();

    $this->sut->processDecay();
  }

  /**
   * @covers ::queueProcessDecay
   * @dataProvider providerQueueProcessDecay
   */
  public function testQueueProcessDecay($profile, $halfLife, $cutoff, $initialEnergy, $elapsedTime, $resultEnergy) {

    $fieldConfig = $this->prophesize(FieldStorageConfigInterface::class);
    $fieldConfig->getTargetEntityTypeId()->willReturn('entity_test');
    $fieldConfig->get('field_name')->willReturn('ra_field');
    $fieldConfig->getSetting('profile')->willReturn($profile);
    $fieldConfig->getSetting('halflife')->willReturn($halfLife);
    $fieldConfig->getSetting('cutoff')->willReturn($cutoff);


    $fieldItemList = $this->prophesize(FieldItemListInterface::class);
    $fieldItemList->getValue()->willReturn([
      [
        'energy' => $initialEnergy,
        'timestamp' => $this->requestTime - $elapsedTime,
      ],
    ]);
    if ($resultEnergy) {
      $fieldItemList->setValue([
        'energy' => $resultEnergy,
        'timestamp' => $this->requestTime,
      ])->shouldBeCalled();
    }
    else {
      $fieldItemList->setValue(NULL)->shouldBeCalled();
    }

    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->get('ra_field')->willReturn($fieldItemList->reveal());
    $entity->save()->shouldBeCalled();

    $entityStorage = $this->prophesize(EntityStorageInterface::class);
    $entityStorage->loadMultiple([123])
      ->willReturn([$entity->reveal()]);

    $this->entityTypeManager->getStorage('entity_test')
      ->willReturn($entityStorage->reveal());

    $this->sut->queueProcessDecay($fieldConfig->reveal(), [123]);
  }

  /**
   * @return array
   *   profile, half life, cutoff, initial energy, timestamp, resulting energy
   */
  public function providerQueueProcessDecay() {
    return [
      ['count', 10, 10, 100, 10, 100],
      ['linear', 10, 10, 100, 0, 100],
      ['linear', 10, 10, 100, 10, 90],
      ['linear', 10, 10, 100, 90, NULL],
      ['decay', 10, 10, 100, 0, 100],
      ['decay', 10, 10, 100, 10, 50],
      ['decay', 10, 30, 100, 20, NULL],
      ['decay', 5, 10, 100, 10, 25],
    ];
  }

  /**
   * @covers ::processIncidents
   */
  public function testProcessIncidents() {

    $incidentsByType['entity_type_a'] = [
      'incidentA1',
      'incidentA2',
      'incidentA3',
    ];
    $incidentsByType['entity_type_b'] = [
      'incidentB1',
      'incidentB2',
      'incidentB3',
      'incidentB4',
      'incidentB5',
      'incidentB6',
      'incidentB7',
      'incidentB8',
      'incidentB9',
      'incidentB10',
      'incidentB11',
      'incidentB12',
    ];

    $this->incidentStorage->getIncidentsByType()->willReturn($incidentsByType);
    $this->incidentStorage->clearIncidents()->shouldBeCalled();

    $queue = $this->prophesize(QueueInterface::class);
    $this->queueFactory->get(RadioactivityProcessorInterface::QUEUE_WORKER_INCIDENTS)->willReturn($queue->reveal());
    $queue->createItem([
      'entity_type' => 'entity_type_a',
      'incidents' => [
        0 => 'incidentA1',
        1 => 'incidentA2',
        2 => 'incidentA3',
      ],
    ])->shouldBeCalledTimes(1);
    $queue->createItem([
      'entity_type' => 'entity_type_b',
      'incidents' => [
        0 => 'incidentB1',
        1 => 'incidentB2',
        2 => 'incidentB3',
        3 => 'incidentB4',
        4 => 'incidentB5',
        5 => 'incidentB6',
        6 => 'incidentB7',
        7 => 'incidentB8',
        8 => 'incidentB9',
        9 => 'incidentB10',
      ],
    ])->shouldBeCalledTimes(1);
    $queue->createItem([
      'entity_type' => 'entity_type_b',
      'incidents' => [
        10 => 'incidentB11',
        11 => 'incidentB12',
      ],
    ])->shouldBeCalledTimes(1);

    $this->loggerChannel->notice('Processed @count radioactivity incidents.', ['@count' => 15])
      ->shouldBeCalled();

    $this->sut->processIncidents();
  }

}

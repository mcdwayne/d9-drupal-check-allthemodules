<?php

namespace Drupal\sir_trevor\Tests\Unit\FieldFormatter;

use Drupal\sir_trevor\ComplexDataValueProcessingEvent;
use Drupal\sir_trevor\EventDispatchingDataProcessor;
use Drupal\Tests\sir_trevor\Unit\TestDoubles\ComplexDataValueProcessingEventSubscriberMock;
use Drupal\Tests\sir_trevor\Unit\TestDoubles\EventDispatcherSpy;

/**
 * Class EventDispatchingDataProcessorTest
 *
 * @package Drupal\sir_trevor\Tests\Unit\FieldFormatter
 */
class EventDispatchingDataProcessorTest extends \PHPUnit_Framework_TestCase {
  /** @var EventDispatcherSpy */
  private $dispatcher;
  /** @var EventDispatchingDataProcessor */
  private $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->dispatcher = new EventDispatcherSpy();
    $this->sut = new EventDispatchingDataProcessor($this->dispatcher);
  }

  /**
   * @test
   */
  public function givenEmptyDataObject_noEventIsDispatched() {
    $data = (object) [];

    $returnedData = $this->sut->processData($data);

    self::assertEmpty($this->dispatcher->getDispatchedEvents());
    self::assertEquals($data, $returnedData);
  }

  /**
   * @test
   */
  public function givenNoArraysOrClasses_dataIsReturnedUnchanged() {
    $data = (object) [
      'some key' => 'some string',
    ];

    $returnedData = $this->sut->processData($data);

    self::assertEmpty($this->dispatcher->getDispatchedEvents());
    self::assertEquals($data, $returnedData);
  }

  /**
   * @test
   */
  public function givenDataObjectWithComplexData_dataIsPassedToEventDispatcher() {
    $data = (object) [
      'some key' => (object) ['type' => 'some_type'],
    ];

    $returnedData = $this->sut->processData($data);

    $expectedEvents = [ComplexDataValueProcessingEvent::class];
    self::assertEquals($expectedEvents, $this->dispatcher->getDispatchedEvents());
    self::assertEquals($data, $returnedData);
  }

  /**
   * @test
   */
  public function subscriberForDataObjectWithComplexData() {
    $subscriber = new ComplexDataValueProcessingEventSubscriberMock();
    $replacement = 'some result';
    $subscriber->setReplacementResultForType('some_type', $replacement);
    $this->dispatcher->setSubscriber($subscriber);
    $data = (object) [
      'some key' => (object) ['type' => 'some_type'],
    ];

    $returnedData = $this->sut->processData($data);

    $expectedData = (object) [
      'some key' => $replacement
    ];
    $expectedEvents = [ComplexDataValueProcessingEvent::class];
    self::assertEquals($expectedEvents, $this->dispatcher->getDispatchedEvents());
    self::assertEquals($expectedData, $returnedData);
  }

  /**
   * @test
   */
  public function givenDataObjectWithArrayOfComplexData_eachComplexDataIsPassedToEventDispatcher() {
    $data = (object) [
      'some key' => [
        (object) ['type' => 'some_type'],
        (object) ['type' => 'some_other_type'],
        (object) ['type' => 'another_type'],
      ]
    ];

    $returnedData = $this->sut->processData($data);

    $expectedEvents = [
      ComplexDataValueProcessingEvent::class,
      ComplexDataValueProcessingEvent::class,
      ComplexDataValueProcessingEvent::class,
    ];
    self::assertEquals($expectedEvents, $this->dispatcher->getDispatchedEvents());
    self::assertEquals($data, $returnedData);
  }

  /**
   * @test
   */
  public function subscriberForSingleDataObjectWithComplexData() {
    $subscriber = new ComplexDataValueProcessingEventSubscriberMock();
    $replacement = 'some result';
    $subscriber->setReplacementResultForType('some_type', $replacement);
    $this->dispatcher->setSubscriber($subscriber);
    $data = (object) [
      'some key' => [
        (object) ['type' => 'some_type'],
        (object) ['type' => 'some_other_type'],
        (object) ['type' => 'another_type'],
      ]
    ];

    $returnedData = $this->sut->processData($data);

    $expectedData = (object) [
      'some key' => [
        $replacement,
        (object) ['type' => 'some_other_type'],
        (object) ['type' => 'another_type'],
      ]
    ];
    $expectedEvents = [
      ComplexDataValueProcessingEvent::class,
      ComplexDataValueProcessingEvent::class,
      ComplexDataValueProcessingEvent::class,
    ];
    self::assertEquals($expectedEvents, $this->dispatcher->getDispatchedEvents());
    self::assertEquals($expectedData, $returnedData);
  }

  /**
   * @test
   */
  public function givenDataObjectWithNestedArrayOfComplexData_eachComplexDataIsPassedToEventDispatcher() {
    $data = (object) [
      'some key' => [
        'some other key' => [
          (object) ['type' => 'some_type'],
          (object) ['type' => 'some_other_type'],
          (object) ['type' => 'another_type'],
        ],
      ],
    ];

    $returnedData = $this->sut->processData($data);

    $expectedEvents = [
      ComplexDataValueProcessingEvent::class,
      ComplexDataValueProcessingEvent::class,
      ComplexDataValueProcessingEvent::class,
    ];
    self::assertEquals($expectedEvents, $this->dispatcher->getDispatchedEvents());
    self::assertEquals($data, $returnedData);
  }

  /**
   * @test
   */
  public function subscriberForSingleNestedDataObjectWithComplexData() {
    $subscriber = new ComplexDataValueProcessingEventSubscriberMock();
    $replacement = 'some result';
    $subscriber->setReplacementResultForType('some_type', $replacement);
    $this->dispatcher->setSubscriber($subscriber);
    $data = (object) [
      'some key' => [
        'some other key' => [
          (object) ['type' => 'some_type'],
          (object) ['type' => 'some_other_type'],
          (object) ['type' => 'another_type'],
        ],
      ],
    ];

    $returnedData = $this->sut->processData($data);

    $expectedData = (object) [
      'some key' => [
        'some other key' => [
          $replacement,
          (object) ['type' => 'some_other_type'],
          (object) ['type' => 'another_type'],
        ],
      ]
    ];
    $expectedEvents = [
      ComplexDataValueProcessingEvent::class,
      ComplexDataValueProcessingEvent::class,
      ComplexDataValueProcessingEvent::class,
    ];
    self::assertEquals($expectedEvents, $this->dispatcher->getDispatchedEvents());
    self::assertEquals($expectedData, $returnedData);
  }

}

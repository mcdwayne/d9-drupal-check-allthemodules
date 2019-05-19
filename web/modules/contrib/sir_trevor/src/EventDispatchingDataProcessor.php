<?php

namespace Drupal\sir_trevor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class EventDispatchingDataProcessor
 *
 * @package Drupal\sir_trevor
 */
class EventDispatchingDataProcessor {
  /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface */
  private $eventDispatcher;

  /**
   * EventDispatchingDataProcessor constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   */
  public function __construct(EventDispatcherInterface $eventDispatcher) {
    $this->eventDispatcher = $eventDispatcher;
  }


  /**
   * @param \stdClass $data
   * @return \stdClass
   */
  public function processData(\stdClass $data) {
    $vars = array_keys(get_object_vars($data));

    foreach ($vars as $var) {
      if ($data->{$var} instanceof \stdClass) {
        $data->{$var} = $this->processDataObject($data->{$var});
      }
      elseif (is_array($data->{$var})) {
        $data->{$var} = $this->processDataArray($data->{$var});
      }
    }

    return $data;
  }

  /**
   * @param \stdClass $dataValue
   * @return mixed|\stdClass
   */
  private function processDataValue(\stdClass $dataValue) {
    $vars = array_keys(get_object_vars($dataValue));
    if (in_array('type', $vars)) {
      $event = $this->dispatchProcessingComplexDataValueEvent($dataValue);

      if ($event->hasReplacementValue()) {
        return $event->getReplacementValue();
      }

      if ($event->hasProcessedData()) {
        foreach ($event->getProcessedData() as $key => $data) {
          $dataValue->{$key} = $data;
        }
      }
    }

    return $dataValue;
  }

  /**
   * @param \stdClass $dataValue
   * @return \Drupal\sir_trevor\ComplexDataValueProcessingEvent
   */
  private function dispatchProcessingComplexDataValueEvent(\stdClass $dataValue) {
    $event = new ComplexDataValueProcessingEvent($dataValue->type, $dataValue);
    $eventName = ComplexDataValueProcessingEvent::class;

    /** @var ComplexDataValueProcessingEvent $event */
    $event = $this->eventDispatcher->dispatch($eventName, $event);
    return $event;
  }

  /**
   * @param array $dataArray
   * @return array
   */
  private function processDataArray(array $dataArray) {
    foreach (array_keys($dataArray) as $item) {
      if ($dataArray[$item] instanceof \stdClass) {
        $dataArray[$item] = $this->processDataObject($dataArray[$item]);
      }
      elseif (is_array($dataArray[$item])) {
        $dataArray[$item] = $this->processDataArray($dataArray[$item]);
      }
    }

    return $dataArray;
  }

  /**
   * @param \stdClass $dataObject
   * @return mixed|\stdClass
   */
  private function processDataObject(\stdClass $dataObject) {
    if (empty($dataObject->type)) {
      $dataObject = $this->processData($dataObject);
    }
    else {
      $dataObject = $this->processDataValue($dataObject);
    }
    return $dataObject;
  }
}

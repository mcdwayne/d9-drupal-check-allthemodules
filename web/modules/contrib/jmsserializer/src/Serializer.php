<?php

namespace Drupal\bluebirdday_jms;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Doctrine\Common\Annotations\AnnotationRegistry;
use JMS\Serializer\SerializerInterface;

/**
 * Serializer wrapper class.
 */
class Serializer implements SerializerInterface {

  protected $serializer;

  /**
   * Create our JMS serializer.
   */
  public function __construct() {
    AnnotationRegistry::registerLoader('class_exists');
    $this->serializer = SerializerBuilder::create()
      ->setSerializationContextFactory(function () {
        return SerializationContext::create()->setSerializeNull(TRUE);
      })->build();
  }

  /**
   * {@inheritdoc}
   */
  public function serialize($data, $format = 'json', SerializationContext $context = NULL) {
    return $this->serializer->serialize($data, $format);
  }

  /**
   * {@inheritdoc}
   */
  public function deserialize($data, $type, $format = 'json', DeserializationContext $context = NULL) {
    if (is_object($data)) {
      $data = json_encode($data);
    }
    return $this->serializer->deserialize($data, $type, $format);
  }

  /**
   * Convert data to array.
   *
   * @param mixed $data
   *   Data to be converted to array.
   *
   * @return array|mixed
   *   The data as an array.
   */
  public function toArray($data) {
    return $this->serializer->toArray($data);
  }

}

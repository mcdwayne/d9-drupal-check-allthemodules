<?php

namespace Drupal\acquia_contenthub\EventSubscriber\SerializeContentField;

use Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent;

/**
 * Trait ContentFieldMetadataTrait.
 *
 * @package Drupal\acquia_contenthub\EventSubscriber\SerializeContentField
 */
trait ContentFieldMetadataTrait {

  /**
   * Sets field metadata.
   *
   * @param \Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent $event
   *   Event.
   */
  protected function setFieldMetaData(SerializeCdfEntityFieldEvent $event) {
    $cdf = $event->getCdf();
    $metadata = $cdf->getMetadata();
    $metadata['field'][$event->getFieldName()]['type'] = $event->getField()->getFieldDefinition()->getType();
    $cdf->setMetadata($metadata);
  }

}

<?php

namespace Drupal\entity_resource_layer\EventSubscriber;

use Drupal\Core\EventSubscriber\ExceptionJsonSubscriber as OExceptionJsonSubscriber;
use Drupal\entity_resource_layer\Exception\EntityResourceException;
use Drupal\entity_resource_layer\Exception\EntityResourceInvalidFieldsException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * JSON exception subscriber.
 *
 * @package Drupal\entity_resource_layer\EventSubscriber
 */
class ExceptionJsonSubscriber extends OExceptionJsonSubscriber {

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    return -74;
  }

  /**
   * {@inheritdoc}
   */
  public function on4xx(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();

    // Handle the custom exception which also includes field information.
    if ($exception instanceof EntityResourceException) {
      $data = $exception->getResourceData(FALSE);
    }
    // Default exceptions should be handled by parent.
    else {
      parent::on4xx($event);
      return;
    }

    $response = new JsonResponse($data, $exception->getStatusCode(), $exception->getHeaders());
    $event->setResponse($response);
  }

}

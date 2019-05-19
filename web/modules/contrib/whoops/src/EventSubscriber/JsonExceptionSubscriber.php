<?php

namespace Drupal\whoops\EventSubscriber;

use Symfony\Component\HttpFoundation\JsonResponse;
use Whoops\Handler\JsonResponseHandler;

/**
 * Default handling for JSON, AJAX and Hal-JSON exceptions.
 */
class JsonExceptionSubscriber extends ExceptionSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['json', 'hal_json', 'drupal_modal', 'drupal_dialog', 'drupal_ajax'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandler() {
    $handler = new JsonResponseHandler();
    $handler->addTraceToOutput(TRUE);
    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function exceptionToResponse(\Exception $exception) {
    $response = parent::exceptionToResponse($exception);
    return JsonResponse::fromJsonString($response->getContent(), $response->getStatusCode(), $response->headers->all());
  }

}

<?php

namespace Drupal\pxe;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use TurnerLabs\ValidatingXmlEncoder\Exception\XsdValidationException;

/**
 * Listen for XSD validation exceptions.
 *
 * In the event of an XSD validation, we want to send back the invalid XML with
 * the error in a message at the top of the XML, making sure that a 503 response
 * is still sent.
 */
class XsdValidationExceptionSubscriber implements EventSubscriberInterface {

  /**
   * Handles errors for this subscriber.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    if ($exception instanceof XsdValidationException) {
      // Add a comment to the top of the XML with the XSD validation error
      // message. This way we can still send the invalid XML for the developer
      // to inspect, rather than the alternative of just letting Drupal send a
      // generic 500 error.
      $document = $exception->getInvalidXmlDocument();
      $comment = $document->createComment(sprintf("XSD validation failed. Error message follows.\nNOTE: reported line numbers are affected by the inclusion of this error message:\n\n%s.", $exception->getMessage()));
      $document->documentElement->insertBefore($comment, $document->documentElement->firstChild);

      $response = new Response($document->saveXML(), Response::HTTP_INTERNAL_SERVER_ERROR);
      $response->setContent($document->saveXML());
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // We want to go last, so we set a really low priority. The
    // DefaultExceptionSubscriber sets the response object, so we need to go
    // after it so as to override it.
    $events[KernelEvents::EXCEPTION][] = ['onException', 0];
    return $events;
  }

}

<?php

namespace Drupal\whoops\EventSubscriber;

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Run as Whoops;
use Whoops\Handler\PrettyPageHandler;

/**
 * Class ExceptionSubscriber.
 *
 * Register shutdown functions and handle caught exceptions.
 */
class ExceptionSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    $events[KernelEvents::EXCEPTION][] = ['onException'];
    return $events;
  }

  /**
   * Get an instance of Whoops.
   *
   * This will instantiate an instance of Whoops and will retrieve the same
   * isntance the next time the event subscriber is requirested.
   *
   * @return \Whoops\Run
   *   The Whoops\Run controller.
   */
  private function getWhoops() {
    static $whoops;

    if (!is_null($whoops)) {
      return $whoops;
    }

    $whoops = new Whoops;
    $whoops->pushHandler(new PrettyPageHandler);
    return $whoops;
  }

  /**
   * Register the Whoops! error handler on request.
   *
   * This is done so that any error that Drupal doesn't specifically catch that
   * Whoops! can catch will. This allows us to display the error page when
   * run time errors are shown.
   */
  public function onRequest() {
    $whoops = $this->getWhoops();
    $whoops->register();

    // Ensure that Drupal registers the shutdown function.
    ErrorHandler::register([$whoops, Whoops::ERROR_HANDLER]);
    ExceptionHandler::register([$whoops, Whoops::EXCEPTION_HANDLER]);
    drupal_register_shutdown_function([$whoops, Whoops::SHUTDOWN_HANDLER]);
  }

  /**
   * Handles the error and renders a result.
   *
   * This event is required as Drupal will catch some throwables and display
   * a simple error page. We intercept after Drupal has prepared the response
   * and get Whoops to process the same exception so we can display the output.
   *
   * @see Drupal\Core\EventSubscriber\DefaultExceptionSubscriber
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The exception event object.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $whoops = $this->getWhoops();
    $exception = $event->getException();

    if ($this->getFormat($event->getRequest()) === 'json') {
      $jsonHandler = new JsonResponseHandler();
      $jsonHandler->setJsonApi(TRUE);
      $whoops->pushHandler($jsonHandler);
    }

    $output = $whoops->handleException($exception);
    $response = new Response($output);

    $event->setResponse($response);
  }

  /**
   * Gets the error-relevant format from the request.
   *
   * @see Drupal\Core\EventSubscriber\DefaultExceptionSubscriber::getFormat
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return string
   *   The format as which to treat the exception.
   */
  protected function getFormat(Request $request) {
    $format = $request->query->get(MainContentViewSubscriber::WRAPPER_FORMAT, $request->getRequestFormat());

    // These are all JSON errors for our purposes. Any special handling for
    // them can/should happen in earlier listeners if desired.
    if (in_array($format, ['drupal_modal', 'drupal_dialog', 'drupal_ajax'])) {
      $format = 'json';
    }

    // Make an educated guess that any Accept header type that includes "json"
    // can probably handle a generic JSON response for errors. As above, for
    // any format this doesn't catch or that wants custom handling should
    // register its own exception listener.
    foreach ($request->getAcceptableContentTypes() as $mime) {
      if (strpos($mime, 'html') === FALSE && strpos($mime, 'json') !== FALSE) {
        $format = 'json';
      }
    }

    return $format;
  }

}

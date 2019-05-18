<?php

namespace Drupal\js;

use Drupal\Core\Render\AttachmentsResponseProcessorInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * RouteSubscriber.
 */
class EventSubscriber extends RouteSubscriberBase {

  /**
   * @var \Drupal\js\Js
   */
  protected $js;

  /**
   * The AJAX response attachments processor service.
   *
   * @var \Drupal\Core\Render\AttachmentsResponseProcessorInterface
   */
  protected $jsAttachmentsProcessor;

  /**
   * EventSubscriber constructor.
   *
   * @param \Drupal\js\Js $js
   *   The JS Callback service.
   * @param \Drupal\Core\Render\AttachmentsResponseProcessorInterface $js_attachments_processor
   *   The JS response attachments processor service.
   */
  public function __construct(Js $js, AttachmentsResponseProcessorInterface $js_attachments_processor) {
    $this->js = $js;
    $this->jsAttachmentsProcessor = $js_attachments_processor;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Change the  '/js' endpoint to something else.
    if (($endpoint = \Drupal::config('js.settings')->get('endpoint')) && $endpoint !== '/js' && ($route = $collection->get('js.callback'))) {
      $route->setPath($endpoint);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[KernelEvents::EXCEPTION] = 'onException';
    $events[KernelEvents::REQUEST] = 'onRequest';
    $events[KernelEvents::RESPONSE][] = ['onResponse', -1000];
    return $events;
  }

  /**
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $request = $event->getRequest();
    if ($this->js->isExecuting($request)) {
      $this->js->exceptionHandler($event->getException());
    }
  }

  /**
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function onRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    if ($this->js->isExecuting($request)) {
      // Immediately start capturing any output.
      ob_start();

      // Override error and exception handlers to capture output.
      if (!$this->js->silencePhpErrors()) {
        set_error_handler([$this->js, 'errorHandler']);
        set_exception_handler([$this->js, 'exceptionHandler']);
        register_shutdown_function([$this->js, 'fatalErrorHandler']);
      }
    }
  }

  /**
   * Renders the ajax commands right before preparing the result.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event, which contains the possible AjaxResponse object.
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if ($response instanceof JsResponse) {
      $this->jsAttachmentsProcessor->processAttachments($response);

      // Correct mime type.
      $response->setHeader('Content-Type', $response->getMimeType() . '; charset=utf-8');
    }
  }

}

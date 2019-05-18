<?php

namespace Drupal\redirectless\EventSubscriber;

use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber to return the page behind a redirect instead a redirect.
 */
class RedirectLessResponseSubscriber implements EventSubscriberInterface {

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * A list of supported redirect response HTTP status codes.
   *
   * @var array
   */
  protected $allowedRedirectResponseCodes = [
    Response::HTTP_FOUND => TRUE,
    Response::HTTP_SEE_OTHER => TRUE,
    Response::HTTP_TEMPORARY_REDIRECT,
  ];

  /**
   * RedirectLessResponseSubscriber constructor.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP kernel.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   * The theme manager.
   */
  public function __construct(HttpKernelInterface $http_kernel, RequestStack $request_stack, ThemeManagerInterface $theme_manager) {
    $this->httpKernel = $http_kernel;
    $this->requestStack = $request_stack;
    $this->themeManager = $theme_manager;
  }

  /**
   * Transforms a redirect response to a real response.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    $response = $event->getResponse();
    // Cover only normal redirects and continue only if this is the response for
    // the master request.
    if ($response instanceof LocalRedirectResponse && isset($this->allowedRedirectResponseCodes[$response->getStatusCode()]) && $event->isMasterRequest()) {
      // Try to allocate enough time for the new request.
      drupal_set_time_limit(ini_get('max_execution_time'));
      // @todo what to do about the idle timeout / proxy timeout when running
      // through apache and using mod_fcgid or mod_proxy_fcgi?

      // Clear request related caches and remove all the requests as the
      // request we'll make should be a made as a master request.
      $this->themeManager->resetActiveTheme();

      // Remove the requests from the request stack so that only the new one is
      // present there.
      while ($this->requestStack->pop()) {}

      // Create the sub-request and push it to the stack.
      $target_url = $response->getTargetUrl();
      $current_request = $event->getRequest();
      $new_request = Request::create($target_url, 'GET', [], $current_request->cookies->all(), [], $current_request->server->all());
      $new_request->attributes->set('redirectless_request', $target_url);

      // In order to send the response the request will be needed. As we've
      // interrupted the previous one we'll finish with the sub request.
      $this->requestStack->push($new_request);

      /** @var \Drupal\Core\Render\HtmlResponse $response_sub */
      $response_sub = $this->httpKernel->handle($new_request, HttpKernelInterface::MASTER_REQUEST);
      $event->setResponse($response_sub);
      // Disable caching for redirectless responses. Caching is disabled for
      // authenticated users anyway.
      $response_sub->headers->set('Cache-Control', 'no-cache, must-revalidate');

      // As we cannot override DrupalKernel::handle(), which is calling
      // \Symfony\Component\HttpFoundation\Response::prepare() and passing into
      // it the initial request we have no other possibility but to exchange all
      // the request object properties with those of the new request.
      $exchange_objects = function (Request $new_object) {
        foreach (get_object_vars($new_object) as $name => $value) {
          $this->$name = $value;
        }
      };
      $exchange_objects = $exchange_objects->bindTo($current_request, $current_request);
      $exchange_objects($new_request);
    }
  }

  /**
   * Attaches the redirectLess library if the request is flagged for it.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespondEarly(FilterResponseEvent $event) {
    $target_url = $this->requestStack->getMasterRequest()->attributes->get('redirectless_request');
    if ($target_url) {
      $response = $event->getResponse();
      $response->addAttachments(
        [
          'library' => ['redirectless/redirectless'],
          'drupalSettings' => ['redirectLessUrl' => $target_url],
        ]
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run before the attachments are processed by
    // \Drupal\Core\EventSubscriber\HtmlResponseSubscriber::onRespond().
    $events[KernelEvents::RESPONSE][] = ['onRespondEarly', 3];

    // Run as the last possible subscriber.
    $events[KernelEvents::RESPONSE][] = ['onRespond', -100000];

    return $events;
  }

}

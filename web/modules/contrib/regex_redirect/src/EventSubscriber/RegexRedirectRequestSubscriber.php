<?php

namespace Drupal\regex_redirect\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\regex_redirect\RegexRedirectRepository;
use Drupal\redirect\Exception\RedirectLoopException;
use Drupal\redirect\RedirectChecker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RequestContext;

/**
 * Regex redirect subscriber for controller requests.
 */
class RegexRedirectRequestSubscriber implements EventSubscriberInterface {

  /**
   * RegexRedirectRepository object.
   *
   * @var \Drupal\regex_redirect\RegexRedirectRepository
   */
  protected $regexRedirectRepository;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;


  /**
   * RedirectChecker object.
   *
   * @var \Drupal\redirect\RedirectChecker
   */
  protected $redirectChecker;

  /**
   * The request context.
   *
   * @var \Symfony\Component\Routing\RequestContext
   */
  protected $context;

  /**
   * A path processor manager for resolving the system path.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * Constructs a RegexRedirectRequestSubscriber object.
   *
   * @param \Drupal\regex_redirect\RegexRedirectRepository $regex_redirect_repository
   *   The redirect entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\redirect\RedirectChecker $redirect_checker
   *   The redirect checker service.
   * @param \Symfony\Component\Routing\RequestContext $context
   *   Request context.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   Path processor.
   */
  public function __construct(RegexRedirectRepository $regex_redirect_repository, LanguageManagerInterface $language_manager, RedirectChecker $redirect_checker, RequestContext $context, InboundPathProcessorInterface $path_processor) {
    $this->regexRedirectRepository = $regex_redirect_repository;
    $this->languageManager = $language_manager;
    $this->redirectChecker = $redirect_checker;
    $this->context = $context;
    $this->pathProcessor = $path_processor;
  }

  /**
   * Handles the redirect if any found.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   *
   * @throws \Drupal\redirect\Exception\RedirectLoopException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function onKernelRequestCheckRegexRedirect(GetResponseEvent $event) {
    // Get a clone of the request. During inbound processing the request
    // can be altered. Allowing this here can lead to unexpected behavior.
    // For example the path_processor.files inbound processor provided by
    // the system module alters both the path and the request; only the
    // changes to the request will be propagated, while the change to the
    // path will be lost.
    $request = clone $event->getRequest();

    if (!$this->redirectChecker->canRedirect($request)) {
      return;
    }

    // Do the inbound processing so that for example language prefixes are
    // removed.
    $path = $this->pathProcessor->processInbound($request->getPathInfo(), $request);
    $path = ltrim($path, '/');

    $this->context->fromRequest($request);

    // Retrieve a matching redirect or set a 503.
    try {
      /** @var \Drupal\regex_redirect\Entity\RegexRedirect $redirect */
      $redirect = $this->regexRedirectRepository->findMatchingRedirect($path, $this->languageManager->getCurrentLanguage()->getId());
    }
    catch (RedirectLoopException $e) {
      // This uses the RedirectLoopException as defined in the redirect
      // contrib module.
      $response = new Response();
      $response->setStatusCode(503);
      $response->setContent('Service unavailable');
      $event->setResponse($response);
      return;
    }

    // Set the response.
    if (!empty($redirect)) {
      // Handle internal path.
      $url = $redirect->getRedirectUrl();
      // Fix the routed url.
      if (!$url->isRouted()) {
        $uri = str_replace('base:route:', 'route:', $url->getUri());
        $url = Url::fromUri($uri);
      }

      // Retrieve a response without headers.
      $response = new TrustedRedirectResponse($url->setAbsolute()->toString(), $redirect->getStatusCode(), []);
      $response->addCacheableDependency($redirect);
      $event->setResponse($response);
    }
  }

  /**
   * Prior to set the response it check if we can redirect.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event object.
   * @param \Drupal\Core\Url $url
   *   The Url where we want to redirect.
   */
  protected function setResponse(GetResponseEvent $event, Url $url) {
    // Set the response if the regex redirect is valid.
    $request = $event->getRequest();
    $this->context->fromRequest($request);

    parse_str($request->getQueryString(), $query);
    $url->setOption('query', $query);
    $url->setAbsolute(TRUE);

    // We can only check access for routed URLs.
    if (!$url->isRouted() || $this->redirectChecker->canRedirect($request, $url->getRouteName())) {
      // Add the 'rendered' cache tag, so that we can invalidate all responses
      // when settings are changed.
      $response = new TrustedRedirectResponse($url->toString(), 301);
      $response->addCacheableDependency(CacheableMetadata::createFromRenderArray([])->addCacheTags(['rendered']));
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This needs to run before RouterListener::onKernelRequest(), which has
    // a priority of 32. Otherwise, that aborts the request if no matching
    // route is found.
    $events[KernelEvents::REQUEST][] = ['onKernelRequestCheckRegexRedirect', 33];
    return $events;
  }

}

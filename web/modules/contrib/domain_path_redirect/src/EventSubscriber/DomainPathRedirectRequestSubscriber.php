<?php

namespace Drupal\domain_path_redirect\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\redirect\Exception\RedirectLoopException;
use Drupal\redirect\RedirectChecker;
use Drupal\domain_path_redirect\DomainPathRedirectRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RequestContext;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Redirect subscriber for controller requests.
 */
class DomainPathRedirectRequestSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The redirect entity repository.
   *
   * @var \Drupal\domain_path_redirect\DomainPathRedirectRepository
   */
  protected $domainPathRedirectRepository;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The alias manager service.
   *
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $aliasManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The redirect checker service.
   *
   * @var \Drupal\redirect\RedirectChecker
   */
  protected $checker;

  /**
   * Request context.
   *
   * @var \Symfony\Component\Routing\RequestContext
   */
  protected $context;

  /**
   * Path processor manager.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The URL generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * DomainPathRedirectRequestSubscriber constructor.
   *
   * @param \Drupal\domain_path_redirect\DomainPathRedirectRepository $domain_path_redirect_repository
   *   The redirect entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory service.
   * @param \Drupal\Core\Path\AliasManager $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\redirect\RedirectChecker $checker
   *   The redirect checker service.
   * @param \Symfony\Component\Routing\RequestContext $context
   *   The router request context.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The inbound path processor.
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   The domain negotiator.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(DomainPathRedirectRepository $domain_path_redirect_repository, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config, AliasManager $alias_manager, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, RedirectChecker $checker, RequestContext $context, InboundPathProcessorInterface $path_processor, DomainNegotiatorInterface $domain_negotiator, LoggerChannelFactoryInterface $logger, UrlGeneratorInterface $url_generator, MessengerInterface $messenger, RouteMatchInterface $route_match) {
    $this->domainPathRedirectRepository = $domain_path_redirect_repository;
    $this->languageManager = $language_manager;
    $this->config = $config->get('redirect.settings');
    $this->aliasManager = $alias_manager;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->checker = $checker;
    $this->context = $context;
    $this->pathProcessor = $path_processor;
    $this->domainNegotiator = $domain_negotiator;
    $this->logger = $logger;
    $this->urlGenerator = $url_generator;
    $this->messenger = $messenger;
    $this->routeMatch = $route_match;
  }

  /**
   * Handles the domain redirect if any found.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestCheckDomainPathRedirect(GetResponseEvent $event) {
    // Get a clone of the request. During inbound processing the request
    // can be altered. Allowing this here can lead to unexpected behavior.
    // For example the path_processor.files inbound processor provided by
    // the system module alters both the path and the request; only the
    // changes to the request will be propagated, while the change to the
    // path will be lost.
    $request = clone $event->getRequest();

    if (!$this->checker->canRedirect($request)) {
      return;
    }

    // Get URL info and process it to be used for hash generation.
    parse_str($request->getQueryString(), $request_query);

    // Do the inbound processing so that for example language prefixes are
    // removed.
    $path = $this->pathProcessor->processInbound($request->getPathInfo(), $request);
    $path = trim($path, '/');
    if (!$domain = $this->domainNegotiator->getActiveDomain(TRUE)) {
      return;
    }

    $this->context->fromRequest($request);
    try {
      $redirect = $this->domainPathRedirectRepository->findMatchingRedirect($path, $domain->id(), $request_query, $this->languageManager->getCurrentLanguage()->getId());
    }
    catch (RedirectLoopException $e) {
      $this->logger->get('redirect')->warning($e->getMessage());
      $response = new Response();
      $response->setStatusCode(503);
      $response->setContent('Service unavailable');
      $event->setResponse($response);
      return;
    }

    if (!empty($redirect)) {
      // Handle internal path.
      $url = $redirect->getRedirectUrl();
      if ($this->config->get('passthrough_querystring')) {
        $url->setOption('query', (array) $url->getOption('query') + $request_query);
      }
      $headers = [
        'X-Redirect-ID' => $redirect->id(),
      ];
      $response = new TrustedRedirectResponse($url->setAbsolute()->toString(), $redirect->getStatusCode(), $headers);
      $response->addCacheableDependency($redirect);
      $event->setResponse($response);
    }
  }

  /**
   * Redirect to domain redirects listing page if there is no active domain.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestCheckActiveDomain(GetResponseEvent $event) {
    $request = $event->getRequest();
    $this->context->fromRequest($request);
    $route_name = $this->routeMatch->getRouteName();
    if (!$this->domainNegotiator->getActiveDomain(TRUE) && $route_name == 'domain_path_redirect.add') {
      // Redirect to domain redirects listing page.
      $this->messenger->addError($this->t('There is no active domain. You should add at least one domain to create redirect.'));
      $response = new TrustedRedirectResponse($this->urlGenerator->generateFromRoute('domain_path_redirect.list'), 301);
      $event->setResponse($response);
    }
  }

  /**
   * Prior to set the response it check if we can redirect.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   * @param \Drupal\Core\Url $url
   *   The Url where we want to redirect.
   */
  protected function setResponse(GetResponseEvent $event, Url $url) {
    $request = $event->getRequest();
    $this->context->fromRequest($request);

    parse_str($request->getQueryString(), $query);
    $url->setOption('query', $query);
    $url->setAbsolute(TRUE);

    // We can only check access for routed URLs.
    if (!$url->isRouted() || $this->checker->canRedirect($request, $url->getRouteName())) {
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
    $events[KernelEvents::REQUEST][] = ['onKernelRequestCheckDomainPathRedirect', 33];
    $events[KernelEvents::REQUEST][] = ['onKernelRequestCheckActiveDomain', 31];
    return $events;
  }

}

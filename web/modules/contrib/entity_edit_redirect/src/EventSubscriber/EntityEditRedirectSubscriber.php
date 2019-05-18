<?php

namespace Drupal\entity_edit_redirect\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;

/**
 * Entity edit redirect subscriber to redirect to external site.
 */
class EntityEditRedirectSubscriber implements EventSubscriberInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The base redirect url.
   *
   * @var string
   */
  protected $baseRedirectUrl;

  /**
   * If current url should be append as destination.
   *
   * @var bool
   */
  protected $appendDestination;

  /**
   * Destination will be appended as a querystring specified here.
   *
   * @var string
   */
  protected $destinationQuerystring;

  /**
   * List of entity edit path patterns for entity types (and bundles).
   *
   * @var bool
   */
  protected $entityEditPathPatterns;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The module configuration.
   */
  public function __construct(RouteMatchInterface $route_match, RouteProviderInterface $route_provider, EntityTypeManagerInterface $entity_type_manager, ImmutableConfig $config) {
    $this->routeMatch = $route_match;
    $this->routeProvider = $route_provider;
    $this->entityTypeManager = $entity_type_manager;
    $this->baseRedirectUrl = $config->get('base_redirect_url');
    $this->appendDestination = $config->get('append_destination');
    $this->destinationQuerystring = $config->get('destination_querystring');
    $this->entityEditPathPatterns = $config->get('entity_edit_path_patterns');
  }

  /**
   * If current route is considered content entity edit route.
   *
   * @return bool
   *   True if url is considered content entity url, false otherwise.
   */
  protected function isEntityEditRoute() {
    // Entity edit url has one single route parameter, which matches the entity
    // type of that entity. Check if number of route parameters equals one.
    $route_parameters = $this->routeMatch->getParameters()->all();
    if (count($route_parameters) !== 1) {
      return FALSE;
    }
    // Drupal content entities use to follow the pattern for their edit
    // routes which is usually entity.{entity_type}.edit_form so check if
    // pattern matches.
    $entity_type = key($route_parameters);
    return $this->routeMatch->getRouteName() == 'entity.' . $entity_type . '.edit_form';
  }

  /**
   * Get entity edit path for given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return bool|string
   *   Entity edit path or false if either entity does not apply for edit path
   *   or entity could not be loaded.
   */
  protected function getEntityEditPath(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    // False if no path patterns are configured for this entity type.
    if (!isset($this->entityEditPathPatterns[$entity_type])) {
      return FALSE;
    }
    // Check if edit entity path pattern is array which would mean that path
    // pattern is configured per bundle for current entity type. Otherwise
    // (scalar case) would mean it's configured for entity type in general.
    $entity_bundle = $entity->bundle();
    if (is_array($this->entityEditPathPatterns[$entity_type])) {
      // Check if path pattern applies for current entity bundle.
      if (!isset($this->entityEditPathPatterns[$entity_type][$entity_bundle])) {
        return FALSE;
      }
      $path_pattern = $this->entityEditPathPatterns[$entity_type][$entity_bundle];
    }
    else {
      $path_pattern = $this->entityEditPathPatterns[$entity_type];
    }
    return str_replace('{uuid}', $entity->uuid(), $path_pattern);
  }

  /**
   * Check if route exists.
   *
   * @param string $route_name
   *   Route name.
   *
   * @return bool
   *   True if route with given name exists, false otherwise.
   */
  protected function routeExists($route_name) {
    try {
      $route = $this->routeProvider->getRouteByName($route_name);
    }
    catch (\Exception $e) {
    }
    return $route instanceof Route ? TRUE : FALSE;
  }

  /**
   * Get canonical entity url for given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return bool|string
   *   Url or false if cannot be obtained.
   */
  protected function getCanonicalEntityUrl(EntityInterface $entity) {
    // Pattern for canonical entity routes is entity.{entity_type}.canonical.
    $canonical_route = 'entity.' . $entity->getEntityTypeId() . '.canonical';
    if ($this->routeExists($canonical_route)) {
      $url_object = Url::fromRoute($canonical_route, [$entity->getEntityTypeId() => $entity->id()], ['absolute' => TRUE]);
      $url = $url_object->toString();
      if ($url) {
        return $url;
      }
    }
    return FALSE;
  }

  /**
   * Get the destination based on provided request.
   *
   * Current route cannot be used as destination since it is entity edit route.
   * Every time it is accessed it is automatically redirected to external host.
   * It makes no sense to return back here, since the very same redirect would
   * happen again. It would be impossible to return back to this origin.
   *
   * Instead we first try to get the destination from query string parameter
   * presented in current url (most of the time it is '/admin/content'). If such
   * a query string is not presented in url we try to use referer, but only if
   * it's internal. Last option is base url of current origin.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return string
   *   Destination url.
   */
  protected function getDestination(Request $request, EntityInterface $entity) {
    // First check if destination query string is presented.
    $destination_querystring = $request->query->get('destination');
    if ($destination_querystring) {
      $url = Url::fromUserInput('/'. ltrim($destination_querystring, '/'), ['absolute' => TRUE]);
      return $url->toString();
    }
    // As a second option try to get referer. This is useful for example
    // when clicking contextual edit link while being on content view page.
    $referer = $request->headers->get('referer');
    if ($referer && UrlHelper::externalIsLocal($referer, $request->getSchemeAndHttpHost())) {
      return $referer;
    }
    // Third option is to obtain canonical entity url.
    if ($canonical_entity_url = $this->getCanonicalEntityUrl($entity)) {
      return $canonical_entity_url;
    }
    // As a last option simply return base url of current origin.
    return $request->getSchemeAndHttpHost();
  }

  /**
   * Handles the entity edit redirect.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespondEntityEditRedirect(FilterResponseEvent $event) {
    // If no base redirect url is set then no redirect can happen.
    if (!$this->baseRedirectUrl) {
      return;
    }
    // Current route must be an entity edit route.
    if (!$this->isEntityEditRoute()) {
      return;
    }
    // Get entity as it is first route parameter for entity edit routes.
    $route_parameters = $this->routeMatch->getParameters()->all();
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = reset($route_parameters);
    if (!$path = $this->getEntityEditPath($entity)) {
      return;
    }
    // Build entity edit url.
    $entity_edit_url = $this->baseRedirectUrl . '/' . ltrim($path, '/');
    if ($this->appendDestination) {
      $request = $event->getRequest();
      $entity_edit_url .= '?' . $this->destinationQuerystring . '=' . $this->getDestination($request, $entity);
    }
    // Redirect to configured remote.
    $response = new TrustedRedirectResponse($entity_edit_url, 301);
    $event->setResponse($response);
    $event->stopPropagation();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run as soon as possible.
    $events[KernelEvents::RESPONSE][] = ['onRespondEntityEditRedirect', 1000];
    return $events;
  }

}

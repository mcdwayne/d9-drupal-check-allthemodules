<?php

/**
 * @file
 * Contains \Drupal\block_page\EventSubscriber\RouteParamContext.
 */

namespace Drupal\block_page\EventSubscriber;

use Drupal\block_page\Event\BlockPageContextEvent;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\TypedData\TypedDataManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Sets values from the route parameters as a context.
 */
class RouteParamContext implements EventSubscriberInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new CurrentUserContext.
   *
   * @param \Drupal\Core\Routing\RouteProvider $route_provider
   *   The route provider.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RouteProvider $route_provider, RequestStack $request_stack) {
    $this->routeProvider = $route_provider;
    $this->requestStack = $request_stack;
  }

  /**
   * Adds in the current user as a context.
   *
   * @param \Drupal\block_page\Event\BlockPageContextEvent $event
   *   The block page context event.
   */
  public function onBlockPageContext(BlockPageContextEvent $event) {
    $request = $this->requestStack->getCurrentRequest();
    $block_page = $event->getBlockPage();
    $routes = $this->routeProvider->getRoutesByPattern('/' . $block_page->getPath())->all();
    $route = reset($routes);

    if ($route_contexts = $route->getOption('parameters')) {
      foreach ($route_contexts as $route_context_name => $route_context) {
        // Skip this parameter.
        if ($route_context_name == 'block_page') {
          continue;
        }

        $context = new Context($route_context);
        if ($request->attributes->has($route_context_name)) {
          $context->setContextValue($request->attributes->get($route_context_name));
        }
        else {
          // @todo Find a way to add in a fake value for configuration.
        }
        $block_page->addContext($route_context_name, $context);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['block_page_context'][] = 'onBlockPageContext';
    return $events;
  }

}

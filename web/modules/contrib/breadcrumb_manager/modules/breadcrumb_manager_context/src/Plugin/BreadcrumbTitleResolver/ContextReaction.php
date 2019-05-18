<?php

namespace Drupal\breadcrumb_manager_context\Plugin\BreadcrumbTitleResolver;

use Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolverBase;
use Drupal\context\ContextManager;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ContextReaction.
 *
 * @BreadcrumbTitleResolver(
 *   id = "context_reaction",
 *   label = @Translation("Context Reaction"),
 *   description = @Translation("Resolve title from a Context reaction."),
 *   weight = 0,
 *   enabled = FALSE
 * )
 *
 * @package Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolver
 */
class ContextReaction extends BreadcrumbTitleResolverBase {

  /**
   * The Module Handler Service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Context Manager Service.
   *
   * @var \Drupal\context\ContextManager
   */
  protected $contextManager;

  /**
   * The Request Stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Breadcrumb Manager cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandlerInterface $module_handler,
    ContextManager $context_manager,
    RequestStack $request_stack,
    CacheBackendInterface $cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->contextManager = $context_manager;
    $this->requestStack = $request_stack;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('context.manager'),
      $container->get('request_stack'),
      $container->get('cache.breadcrumb_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    if ($this->moduleHandler->moduleExists('context') === FALSE) {
      return FALSE;
    }
    return parent::isActive();
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle($path, Request $request, RouteMatchInterface $route_match) {
    foreach ($this->getReactions($path, $request) as $reaction) {
      $title = $reaction->execute();
      if (!empty($title)) {
        return $title;
      }
    };
    return NULL;
  }

  /**
   * Get reactions.
   *
   * @param string $path
   *   The path.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request.
   *
   * @return \Drupal\context\ContextReactionInterface[]
   *   An array of Context reactions.
   */
  protected function getReactions($path, Request $request) {
    $cid = "context:$path";
    $cache = $this->cache->get($cid);
    if (!empty($cache->data)) {
      return $cache->data;
    }

    $requestHasChanged = FALSE;
    $currentRequest = $this->requestStack->getCurrentRequest();
    if ($currentRequest->getPathInfo() !== $request->getPathInfo()) {
      // Pop out the original Request.
      $orig = $this->requestStack->pop();
      // Assign original session to the new Request.
      $request->setSession($orig->getSession());
      // Switch the new Request with the old one and evaluate contexts again.
      $this->requestStack->push($request);
      $this->contextManager->evaluateContexts();
      // Push the old Request back.
      $this->requestStack->push($orig);
      // Mark Request as changed.
      $requestHasChanged = TRUE;
    }

    $reactions = $this->contextManager->getActiveReactions('breadcrumb');
    $this->cache->set($cid, $reactions, Cache::PERMANENT, [
      'breadcrumb_manager',
      'breadcrumb_manager_context',
    ]);

    // If Request has changed, evaluate contexts again in order to avoid wrong
    // reactions to be used.
    if ($requestHasChanged) {
      $this->contextManager->evaluateContexts();
    }
    return $reactions;
  }

}

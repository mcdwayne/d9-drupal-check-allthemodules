<?php

namespace Drupal\timed_node_page\Controller;

use Drupal\timed_node_page\TimedNodePagePluginManager;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for the timed node page plugins.
 *
 * Serves for each plugin the current node.
 *
 * @package Drupal\timed_node_page\Controller
 */
class TimedNodePageController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Stores the matching plugin.
   *
   * @var \Drupal\timed_node_page\TimedNodePagePluginManager
   */
  protected $timedNodePlugin;

  /**
   * Stores the current node matched.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $currentNode;

  protected $timedNodePageManager;
  protected $entityTypeManager;
  protected $entityRepository;
  protected $currentRequest;
  protected $currentRouteMatch;

  /**
   * TimedNodePageController constructor.
   *
   * @param \Drupal\timed_node_page\TimedNodePagePluginManager $timedNodePagePluginManager
   *   The timed node plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request stack.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The current route match.
   */
  public function __construct(
    TimedNodePagePluginManager $timedNodePagePluginManager,
    EntityTypeManagerInterface $entityTypeManager,
    EntityRepositoryInterface $entityRepository,
    RequestStack $request,
    CurrentRouteMatch $currentRouteMatch
  ) {
    $this->timedNodePageManager = $timedNodePagePluginManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityRepository = $entityRepository;
    $this->currentRequest = $request->getCurrentRequest();
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.timed_node_page'),
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('request_stack'),
      $container->get('current_route_match')
    );
  }

  /**
   * Displays the current node page for the bundle.
   *
   * @param string $bundle
   *   The bundle.
   *
   * @return array|\Symfony\Component\HttpFoundation\Response
   *   Render array / response.
   */
  public function displayPage($bundle) {
    if (!($pagePlugin = $this->getTimedNodePage($bundle))) {
      throw new NotFoundHttpException();
    }

    if ($pagePlugin->usesCustomResponse()) {
      return $pagePlugin->getCustomResponse();
    }

    if (!($currentNode = $this->getCurrentNode($bundle))) {
      throw new NotFoundHttpException();
    }

    // Set the node on the request as some logic might depend on it.
    $this->currentRequest->attributes->set('node', $currentNode);
    // Reset the route match so next time it's used it's recalculated from the
    // updated request object.
    $this->currentRouteMatch->resetRouteMatch();

    $build = $this->entityTypeManager->getViewBuilder('node')
      ->view($currentNode);

    $pageCacheMetadata = CacheableMetadata::createFromObject($pagePlugin);
    $pageCacheMetadata->applyTo($build);

    return $build;
  }

  /**
   * Callback for the title of the page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The title.
   */
  public function getPageTitle(RouteMatchInterface $routeMatch) {
    if ($node = $this->getCurrentNode($routeMatch->getParameter('bundle'))) {
      return $node->label();
    }

    return $this->t('Not found');
  }

  /**
   * Gets the timed node page plugin for bundle.
   *
   * @param string $bundle
   *   The node bundle.
   *
   * @return \Drupal\timed_node_page\TimedNodePagePluginInterface|null
   *   The timed node page plugin.
   */
  protected function getTimedNodePage($bundle) {
    if (!isset($this->timedNodePlugin)) {
      $this->timedNodePlugin = $this->timedNodePageManager->getBy($bundle);
    }

    return $this->timedNodePlugin;
  }

  /**
   * Gets the current timed page node.
   *
   * @param string $bundle
   *   The node bundle.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\node\NodeInterface|null
   *   The current node, if any.
   */
  protected function getCurrentNode($bundle) {
    if (!isset($this->currentNode)) {
      if ($this->currentNode = $this->getTimedNodePage($bundle)->getCurrentNode()) {
        $this->currentNode = $this->entityRepository->getTranslationFromContext($this->currentNode);
      }
    }

    return $this->currentNode;
  }

}

<?php

namespace Drupal\panels_everywhere\EventSubscriber;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Drupal\Core\Render\RenderEvents;
use Drupal\page_manager\PageVariantInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;

/**
 * Selects the appropriate page display variant from 'site_template'.
 */
class PanelsEverywherePageDisplayVariantSubscriber implements EventSubscriberInterface {

  use ConditionAccessResolverTrait;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Constructs a new PageManagerRoutes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityStorage = $entity_type_manager->getStorage('page');
  }

  /**
   * Selects the page display variant.
   *
   * @param \Drupal\Core\Render\PageDisplayVariantSelectionEvent $event
   *   The event to process.
   */
  public function onSelectPageDisplayVariant(PageDisplayVariantSelectionEvent $event) {
    $route = $event->getRouteMatch()->getRouteObject();

    // if this is an admin path, do not process it
    if ($route->getOption('_admin_route')) {
      return;
    }

    if ($variant = $this->getVariantPlugin($route)) {
      $event->setPluginId($variant->getPluginId());
      $event->setPluginConfiguration($variant->getConfiguration());
      $event->setContexts($variant->getContexts());
      $event->stopPropagation();
    }
  }

  /**
   * Copied from VariantRouteFilter.php
   *
   * Checks access of a page variant.
   *
   * @param \Drupal\page_manager\PageVariantInterface $variant
   *   The page variant.
   *
   * @return bool
   *   TRUE if the route is valid, FALSE otherwise.
   */
  protected function checkVariantAccess(PageVariantInterface $variant) {
    try {
      $access = $variant && $variant->access('view');
    }
    // Since access checks can throw a context exception, consider that as
    // a disallowed variant.
    catch (ContextException $e) {
      $access = FALSE;
    }

    return $access;
  }

  /**
   * Retrieves the display variant plugin for this route, if it exists.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route.
   *
   * @return null|\Drupal\panels_everywhere\Plugin\DisplayVariant\PanelsEverywhereDisplayVariant
   *   The display variant plugin or NULL if non could be found.
   */
  protected function getVariantPlugin(Route $routeObject) {
    $pages = $this->getPagesFor($routeObject);

    if (empty($pages)) {
      return NULL;
    }

    foreach ($pages AS $page_id => $page) {
      foreach ($page->getVariants() AS $variant_id => $variant) {
        if (!$this->checkVariantAccess($variant)) {
          continue;
        }

        $variant_plugin = $variant->getVariantPlugin();

        if ($variant_plugin->getPluginId() == 'panels_everywhere_variant') {
          return $variant_plugin;
        }
      }
    }

    return NULL;
  }

  /**
   * Retrieves the page entity for the given route.
   *
   * @param \Symfony\Component\Routing\Route $routeObject
   *   The route.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The page entity referenced on the route or the 'site_template'
   *   page entity as long as they are enabled.
   *   Otherwise NULL will be returned.
   */
  protected function getPagesFor(Route $routeObject) {
    $pages = [];

    // pass 1 - try getting the page using the overridable getPageEntity function
    if ($routeObject) {
      $pageID = $routeObject->getDefault('page_id');
      if ($pageID) {
        $page = $this->entityStorage->load($pageID);
        if ($page && $page->get('status')) {
          $pages[$pageID] = $this->entityStorage->load($pageID);
        }
      }
    }

    // pass 2 - use the global "Site Template" page
    $site_template = $this->entityStorage->load('site_template');
    if ($site_template && $site_template->get('status')) {
      $pages['site_template'] = $this->entityStorage->load('site_template');
    }

    return $pages;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[RenderEvents::SELECT_PAGE_DISPLAY_VARIANT][] = ['onSelectPageDisplayVariant'];
    return $events;
  }

}

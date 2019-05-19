<?php

namespace Drupal\single_page_site\Controller;

use Drupal\single_page_site\Event\EventSinglePageSiteAlterOutput;
use Drupal\single_page_site\Event\SinglePageSiteEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\single_page_site\Manager\SinglePageSiteManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class SinglePageSiteController.
 *
 * @package Drupal\single_page_site\Controller
 */
class SinglePageSiteController extends ControllerBase {

  protected $manager;
  protected $moduleHandler;
  protected $renderer;
  protected $eventDispatcher;

  /**
   * SinglePageSiteController constructor.
   *
   * @param \Drupal\single_page_site\Manager\SinglePageSiteManager $manager
   *   Manager value.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   Module handler value.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer value.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher value.
   */
  public function __construct(SinglePageSiteManager $manager, ModuleHandler $module_handler, RendererInterface $renderer, EventDispatcherInterface $event_dispatcher) {
    $this->manager = $manager;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('single_page_site.manager'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Sets title.
   */
  public function setTitle() {
    return $this->manager->getPageTitle();
  }

  /**
   * Renders single page.
   *
   * @return array
   *   Return the array with render.
   */
  public function render() {
    if ($menu = $this->manager->getMenu()) {
      $items = array();
      $current_item_count = 1;
      // Collect all drupal messages and store them, we will show them later on.
      $messages = drupal_get_messages();
      // Now fetch menu tree.
      $tree = $this->manager->getMenuChildren();

      foreach ($tree as $menu_item) {
        if ($menu_item_details = $this->manager->isMenuItemRenderable($menu_item)) {
          // Get route params.
          $params = $menu_item_details['route_parameters'];
          // Generate href.
          $url = Url::fromRoute($menu_item_details['route_name'], $params);
          $href = $url->toString();
          $internalPath = $url->getInternalPath();
          // Generate anchor.
          $anchor = $this->manager->generateAnchor($href);

          // At this point we can execute request to render content.
          $render = $this->manager->executeAndRenderSubRequest($internalPath);
          $output = is_array($render) ? $this->renderer->render($render) : $render;
          // Let other modules makes changes to $output.
          // This alter hook is deprecated and will be removed in next major
          // release. Use EventSubscriber.
          $this->moduleHandler->alter('single_page_site_output', $output, $current_item_count);

          // Dispatch event to allow other modules to make changes to the
          // output.
          /** @var EventSinglePageSiteAlterOutput $event */
          $event = new EventSinglePageSiteAlterOutput($output, $current_item_count);
          $event = $this->eventDispatcher->dispatch(SinglePageSiteEvents::SINGLE_PAGE_SITE_ALTER_OUTPUT, $event);

          // Build renderable array.
          $item = array(
            'output' => $event->getOutput(),
            'anchor' => $anchor,
            'title' => $menu_item->link->getTitle(),
            'tag' => $this->manager->getTitleTag(),
          );
          array_push($items, $item);
          $current_item_count++;
        }
      }
      // Re-inject the messages.
      foreach ($messages as $type => $data) {
        foreach ($data as $message) {
          drupal_set_message($message, $type);
        }
      }

      // Render output and attach JS files.
      $js_settings = array(
        'menuClass' => $this->manager->getMenuClass(),
        'distanceUp' => $this->manager->getDistanceUp(),
        'distanceDown' => $this->manager->getDistanceDown(),
        'updateHash' => $this->manager->updateHash(),
        'offsetSelector' => $this->manager->getOffsetSelector(),
      );

      $page_content = array(
        '#theme' => 'single_page_site',
        '#items' => $items,
        '#attached' => array(
          'library' => array(
            'single_page_site/single_page_site.scrollspy',
          ),
        ),
      );

      if ($this->manager->getSmoothScrolling()) {
        // Add smooth scrolling.
        $page_content['#attached']['library'][] = 'single_page_site/single_page_site.scroll';
      }
      $page_content['#attached']['drupalSettings']['singlePage'] = $js_settings;

      return $page_content;
    }
    else {
      // If settings aren't set.
      return array(
        '#markup' => $this->t('You have to !configure your single page before you can use it.',
          array('!configure' => Link::fromTextAndUrl(t('configure'), Url::fromRoute('single_page_site.config')))),
      );
    }
  }

}

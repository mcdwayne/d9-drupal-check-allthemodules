<?php

namespace Drupal\single_page_site_next_page\EventSubscriber;

use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\single_page_site\Event\EventSinglePageSiteAlterOutput;
use Drupal\single_page_site\Event\SinglePageSiteEvents;
use Drupal\single_page_site\Manager\SinglePageSiteManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class AlterSinglePageSiteOutput.
 *
 * @package Drupal\single_page_site_newt_page\EventSubscriber
 */
class AlterSinglePageSiteOutput implements EventSubscriberInterface {

  protected $manager;
  protected $menuItems;

  /**
   * AlterSinglePageSiteOutput constructor.
   */
  public function __construct(SinglePageSiteManager $manager) {
    $this->manager = $manager;
    $this->menuItems = $this->setSinglePageItems();
  }

  /**
   * Alters the output of the single page item.
   *
   * @param \Drupal\single_page_site\Event\EventSinglePageSiteAlterOutput $event
   *   Event value.
   */
  public function alterOutput(EventSinglePageSiteAlterOutput $event) {
    $output = $event->getOutput();
    $current_item = $event->getCurrentItemCount();

    $count_menu_items = count($this->menuItems);
    // If item is not last item.
    if ($current_item < $count_menu_items) {
      $menu_item = $this->menuItems[$current_item];
      // Get route params.
      $params = $menu_item['route_parameters'];
      // Fetch href.
      $href = Url::fromRoute($menu_item['route_name'], $params)->toString();
      // Generate valid anchor.
      $anchor = $this->manager->generateAnchor($href);
      // Generate next url.
      // (I know this is bad practice, but I haven't figured out yet how to
      // render a link with only a fragment and no URL).
      $next_page_link = '<a href="#' . $anchor . '" class="to-next-page">' . $menu_item['title'] . '</a>';
      // Attach link to output by creating new markup object.
      $event->setOutput(Markup::create($output . $next_page_link));
    }
  }

  /**
   * Sets single page items (from menu tree).
   *
   * @return array
   *   Return array with items.
   */
  private function setSinglePageItems() {
    $items = &drupal_static(__FUNCTION__);
    if (!isset($items)) {
      $items = array();
      $tree = $this->manager->getMenuChildren();

      foreach ($tree as $menu_item) {
        if ($menu_item_details = $this->manager->isMenuItemRenderable($menu_item)) {
          array_push($items, $menu_item_details);
        }
      }
    }
    return $items;
  }

  /**
   * Function to get Subscribed Events.
   *
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    return [
      SinglePageSiteEvents::SINGLE_PAGE_SITE_ALTER_OUTPUT => [
        ['alterOutput'],
      ],
    ];
  }

}

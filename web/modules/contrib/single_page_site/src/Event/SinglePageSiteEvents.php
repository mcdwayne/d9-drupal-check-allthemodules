<?php

namespace Drupal\single_page_site\Event;

/**
 * Class SinglePageSiteEvents.
 *
 * @package Drupal\single_page_site\Event
 */
final class SinglePageSiteEvents {

  // 'SINGLE_PAGE_SITE_ALTER_OUTPUT' occurs after single page item is rendered
  // but before it is added to de render array of items.
  const SINGLE_PAGE_SITE_ALTER_OUTPUT = 'single_page_site.alter_output';

}

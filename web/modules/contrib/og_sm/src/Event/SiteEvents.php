<?php

namespace Drupal\og_sm\Event;

/**
 * Defines events for sites.
 */
final class SiteEvents {

  /**
   * Name of the event fired when a Site node being inserted or updated.
   *
   * This event allows modules to perform an action whenever a Site node is
   * being inserted or updated. The event listener method receives a
   * \Drupal\og_sm\Event\SiteEvent instance.
   *
   * @Event
   *
   * @see og_sm_node_presave()
   */
  const PRESAVE = 'og_sm.site.presave';

  /**
   * Name of the event fired when a Site node is being inserted.
   *
   * This event allows modules to perform an action whenever a Site node is
   * being inserted. The event listener method receives a
   * \Drupal\og_sm\Event\SiteEvent instance.
   *
   * @Event
   *
   * @see og_sm_node_insert()
   */
  const INSERT = 'og_sm.site.insert';

  /**
   * Name of the event fired when a Site node is being updated.
   *
   * This event allows modules to perform an action whenever a Site node is
   * being updated. The event listener method receives a
   * \Drupal\og_sm\Event\SiteEvent instance.
   *
   * @Event
   *
   * @see og_sm_node_update()
   */
  const UPDATE = 'og_sm.site.update';

  /**
   * Name of the event fired when a Site node is being saved.
   *
   * This event allows modules to perform an action whenever a Site node is
   * being saved. The event listener method receives a
   * \Drupal\og_sm\Event\SiteEvent instance.
   *
   * @Event
   *
   * @see \Drupal\og_sm\OgSm::siteEventDispatch()
   */
  const SAVE = 'og_sm.site.save';

  /**
   * Name of the event fired when a Site node is being deleted.
   *
   * This event allows modules to perform an action whenever a Site node is
   * being deleted. The event listener method receives a
   * \Drupal\og_sm\Event\SiteEvent instance.
   *
   * @Event
   *
   * @see og_sm_node_delete()
   */
  const DELETE = 'og_sm.site.delete';

  /**
   * Name of the event fired after a Site node was inserted.
   *
   * This event allows modules to perform an action after a Site node is
   * was inserted. This allows modules to interact with Sites after the insert
   * queries are stored in the database (after database transaction commit). The
   * event listener method receives a \Drupal\og_sm\Event\SiteEvent instance.
   *
   * @Event
   *
   * @see \Drupal\og_sm\OgSm::siteEventDispatch()
   */
  const POST_INSERT = 'og_sm.site.post_insert';

  /**
   * Name of the event fired after a Site node was updated.
   *
   * This event allows modules to perform an action after a Site node is
   * was updated. This allows modules to interact with Sites after the update
   * queries are stored in the database (after database transaction commit). The
   * event listener method receives a \Drupal\og_sm\Event\SiteEvent instance.
   *
   * @Event
   *
   * @see \Drupal\og_sm\OgSm::siteEventDispatch()
   */
  const POST_UPDATE = 'og_sm.site.post_update';

  /**
   * Name of the event fired after a Site node was saved.
   *
   * This event allows modules to perform an action after a Site node is
   * was saved. This allows modules to interact with Sites after the
   * insert/update queries are stored in the database (after database
   * transaction commit). The event listener method receives a
   * \Drupal\og_sm\Event\SiteEvent instance.
   *
   * @Event
   *
   * @see \Drupal\og_sm\OgSm::siteEventDispatch()
   */
  const POST_SAVE = 'og_sm.site.post_save';

  /**
   * Name of the event fired after a Site node was deleted.
   *
   * This event allows modules to perform an action after a Site node is
   * was deleted. This allows modules to interact with Sites after the delete
   * queries are stored in the database (after database transaction commit). The
   * event listener method receives a \Drupal\og_sm\Event\SiteEvent instance.
   *
   * @Event
   *
   * @see \Drupal\og_sm\OgSm::siteEventDispatch()
   */
  const POST_DELETE = 'og_sm.site.post_delete';

  /**
   * Name of the event fired when a site cache clear is requested.
   *
   * This event allows modules to perform an action to clear a site's cache. The
   * event listener method receives a \Drupal\og_sm\Event\SiteEvent instance.
   *
   * @Event
   *
   * @see \Drupal\og_sm\OgSm::siteEventDispatch()
   */
  const CACHE_CLEAR = 'og_sm.site.cache_clear';

}

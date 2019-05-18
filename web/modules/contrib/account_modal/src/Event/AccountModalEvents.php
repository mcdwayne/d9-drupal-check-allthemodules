<?php

namespace Drupal\account_modal\Event;

/**
 * Defines events for the account_modal module.
 */
final class AccountModalEvents {

  /**
   * Name of the event fired when getting array of supported pages.
   *
   * Fired after the core list of pages is defined.
   *
   * @Event
   *
   * @see \Drupal\account_modal\Event\PagesEvent
   */
  const PAGES = 'account_modal.pages';

}

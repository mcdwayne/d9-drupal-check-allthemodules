<?php

/**
 * @class
 * UI Controller for a Recurly Subscription Plan.
 */
class RecurlySubscriptionPlanUIController extends EntityDefaultUIController {
  /**
   * Overrides hook_menu() defaults.
   */
  public function hook_menu() {
    $items = parent::hook_menu();

    $items[$this->path]['type'] = MENU_LOCAL_TASK;

    // Remove the 'Add subscription plan' link and menu item. Plans must always
    // be created in Recurly and then imported via the Recurly module settings.
    if (isset($items[$this->path . '/add'])) {
      unset($items[$this->path . '/add']);
    }

    return $items;
  }
}

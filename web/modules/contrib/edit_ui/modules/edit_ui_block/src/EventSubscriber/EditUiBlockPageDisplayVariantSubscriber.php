<?php

/**
 * @file
 * Contains \Drupal\edit_ui_block\EventSubscriber\EditBlockUiPageDisplayVariantSubscriber.
 */

namespace Drupal\edit_ui_block\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Drupal\Core\Render\RenderEvents;

/**
 * Selects the edit_ui_block page display variant.
 *
 * @see \Drupal\edit_ui_block\Plugin\DisplayVariant\EditUiBlockPageVariant
 */
class EditUiBlockPageDisplayVariantSubscriber implements EventSubscriberInterface {

  /**
   * Selects the edit_ui_block page display variant.
   *
   * @param \Drupal\Core\Render\PageDisplayVariantSelectionEvent $event
   *   The event to process.
   */
  public function onSelectPageDisplayVariant(PageDisplayVariantSelectionEvent $event) {
    if (edit_ui_block_toolbar_can_activate() && edit_ui_block_toolbar_is_active()) {
      $event->setPluginId('edit_ui_block_page');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RenderEvents::SELECT_PAGE_DISPLAY_VARIANT][] = array('onSelectPageDisplayVariant');
    return $events;
  }

}

<?php

namespace Drupal\context_layout\EventSubscriber;

use Drupal\context\ContextManager;
use Drupal\Core\Render\RenderEvents;
use Drupal\context\Plugin\ContextReaction\Blocks;
use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Selects the block page display variant.
 *
 * @see \Drupal\block\Plugin\DisplayVariant\BlockPageVariant
 */
class LayoutPageDisplayVariantSubscriber implements EventSubscriberInterface {

  /**
   * The context manager service.
   *
   * @var \Drupal\context\ContextManager
   */
  private $contextManager;

  /**
   * Construct event subscriber object.
   *
   * @param \Drupal\context\ContextManager $contextManager
   *   The context manager service.
   */
  public function __construct(ContextManager $contextManager) {
    $this->contextManager = $contextManager;
  }

  /**
   * Selects the context block page display variant.
   *
   * @param \Drupal\Core\Render\PageDisplayVariantSelectionEvent $event
   *   The event to process.
   */
  public function onSelectPageDisplayVariant(PageDisplayVariantSelectionEvent $event) {
    $admin = \Drupal::service('router.admin_context')->isAdminRoute();
    $allow = \Drupal::config('context_layout.settings')->get('admin_allow');

    // Exit listener if contextual layouts are not allowed on admin routes.
    if ($admin && !$allow) {
      return;
    }

    // Activate the context layout page display variant if any of the reactions
    // is a blocks reaction.
    foreach ($this->contextManager->getActiveReactions() as $reaction) {
      if ($reaction instanceof Blocks) {
        $event->setPluginId('context_layout_page');
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RenderEvents::SELECT_PAGE_DISPLAY_VARIANT][] = ['onSelectPageDisplayVariant'];
    return $events;
  }

}

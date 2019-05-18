<?php

namespace Drupal\layout_builder_enhancements\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\layout_builder\LayoutBuilderEvents;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\Plugin\Block\InlineBlock;

/**
 * EventSubscriber for layout render event.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY => [
        'onBuildRender',
        -100,
      ],
    ];
  }

  /**
   * Event render function.
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event) {
    $block = $block = $event->getPlugin();
    if (!$block instanceof InlineBlock) {
      return;
    }

    if (!$event->inPreview()) {
      return;
    }

    $build = $event->getBuild();
    $block->setConfiguration(['view_mode' => 'layout_builder_preview']);
    $build['content'] = $block->build();
    $event->setBuild($build);
  }

}

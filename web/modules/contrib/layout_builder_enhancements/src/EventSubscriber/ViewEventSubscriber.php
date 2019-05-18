<?php

namespace Drupal\layout_builder_enhancements\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\layout_builder_enhancements\Plugin\Block\ViewBlock;
use Drupal\layout_builder\LayoutBuilderEvents;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;

/**
 * EventSubscriber for layout render event.
 */
class ViewEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY => [
        'onBuildRender',
        100,
      ],
    ];
  }

  /**
   * Event render function.
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event) {
    $block = $event->getPlugin();
    if (!$block instanceof ViewBlock) {
      return;
    }
    $build = $event->getBuild();

    $offset = $this->getOffset($event, $block);

    $block->setOffset($offset);
    $block->inPreview($event->inPreview());
    $build['content'] = $block->build();
    $event->setBuild($build);
  }

  /**
   * Helper function for get offset for a component in layout field.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   The event was recieved.
   * @param \Drupal\layout_builder_enhancements\Plugin\Block\ViewBlock $block
   *   The actual block for lookup offset.
   *
   * @return int
   *   The offset for this block.
   */
  protected function getOffset(SectionComponentBuildRenderArrayEvent $event, ViewBlock $block): int {
    $weights =& \drupal_static(__FUNCTION__);
    $component = $event->getComponent();
    if (!isset($weights[$block->getDerivativeId()])) {
      $weights[$block->getDerivativeId()] = [];
      $contexts = $event->getContexts();

      $layoutBuilder = $contexts['layout_builder.entity'];
      $sections = $layoutBuilder->getContextValue()->layout_builder__layout;

      foreach ($sections as $section) {
        $section = $section->section;
        $regions = $section->getLayout()->getPluginDefinition()->getRegionNames();

        foreach ($regions as $region) {
          $sorted = [];
          foreach ($section->getComponentsByRegion($region) as $comp) {
            if ($comp->getPluginId() == $block->getPluginId()) {
              $sorted[$comp->getUuid()] = $comp;
            }
          }
          usort($sorted, function ($a, $b) {
            if ($a->getWeight() == $b->getWeight()) {
                return 0;
            }
            return ($a->getWeight() < $b->getWeight()) ? -1 : 1;
          });
          foreach ($sorted as $comp) {
            $weights[$block->getDerivativeId()][] = $comp;
          }
        }
      }
    }

    foreach ($weights[$block->getDerivativeId()] as $offset => $comp) {
      if ($component->getUuid() == $comp->getUuid()) {
        return $offset;
      }
    }
    return 0;
  }

}

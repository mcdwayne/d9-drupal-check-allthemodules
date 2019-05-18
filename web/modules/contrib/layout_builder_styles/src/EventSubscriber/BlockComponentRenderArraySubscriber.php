<?php

namespace Drupal\layout_builder_styles\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\layout_builder\LayoutBuilderEvents;

/**
 * Class BlockComponentRenderArraySubscriber.
 */
class BlockComponentRenderArraySubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * BlockComponentRenderArraySubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Layout Builder also subscribes to this event to build the initial render
    // array. We use a higher weight so that we execute after it.
    $events[LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY] = ['onBuildRender', 50];
    return $events;
  }

  /**
   * Add each component's block styles to the render array.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   The section component render event.
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event) {
    $build = $event->getBuild();
    // This shouldn't happen - Layout Builder should have already created the
    // initial build data.
    if (empty($build)) {
      return;
    }

    $selectedStyle = $event->getComponent()->get('layout_builder_styles_style');
    if ($selectedStyle) {
      /** @var \Drupal\layout_builder_styles\LayoutBuilderStyleInterface $style */
      $style = $this->entityTypeManager->getStorage('layout_builder_style')->load($selectedStyle);
      if ($style) {
        if (!isset($build['#attributes']['class']) || !is_array($build['#attributes']['class'])) {
          $build['#attributes']['class'] = [];
        }
        $classes = \preg_split('(\r\n|\r|\n)', $style->getClasses());
        $build['#attributes']['class'] = array_merge($build['#attributes']['class'], $classes);

        // Add the chosen style to the render array so we can use it in our
        // theme suggestions alter hook.
        $build['#layout_builder_style'] = $selectedStyle;

        $build['#cache']['tags'][] = 'config:layout_builder_styles.style.' . $style->id();
        $event->setBuild($build);
      }
    }
  }

}

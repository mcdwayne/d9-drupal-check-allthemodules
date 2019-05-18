<?php

namespace Drupal\layout_builder_enhancer\EventSubscriber;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Layout builder enhancer event subscriber.
 */
class LayoutBuilderEnhancerSubscriber implements EventSubscriberInterface {

  /**
   * Route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * LayoutBuilderEnhancerSubscriber constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   Route match service.
   */
  public function __construct(CurrentRouteMatch $route_match, ModuleHandlerInterface $handler) {
    $this->routeMatch = $route_match;
    $this->moduleHandler = $handler;
  }

  /**
   * Subscriber for kernel view.
   */
  public function onKernelView(GetResponseForControllerResultEvent $event) {
    $route_name = $this->routeMatch->getCurrentRouteMatch()->getRouteName();
    $result = $event->getControllerResult();
    switch ($route_name) {
      case 'layout_builder.overrides.node.view':
      case 'entity.node.layout_builder':
        $this->alterBuilder($result);
        break;

      case 'layout_builder.choose_section':
        $this->alterSectionChooser($result);
        break;

      case 'layout_builder.choose_block':
        $this->alterChooser($result);
        break;

      default:
        return;
    }
    $event->setControllerResult($result);
  }

  /**
   * Alters the section chooser.
   */
  protected function alterSectionChooser(&$result) {
    if (empty($result['layouts']['#items'])) {
      return;
    }
    $keys = $this->moduleHandler->invokeAll('layout_builder_enhancer_allowed_layouts');
    $this->moduleHandler->alter('layout_builder_enhancer_allowed_layouts', $keys);
    foreach ($result['layouts']['#items'] as $delta => $item) {
      /** @var \Drupal\Core\Url $url */
      $url = $item['#url'];
      $params = $url->getRouteParameters();
      if (!in_array($params['plugin_id'], $keys)) {
        unset($result['layouts']['#items'][$delta]);
      }
    }
  }

  /**
   * Alter the builder output.
   *
   * @param array $result
   *   The result from the controller.
   */
  protected function alterBuilder(array &$result) {
    $result['#attached']['library'][] = 'layout_builder_enhancer/editor_styling';
  }

  /**
   * Alter the chooser to only allow what we have defined.
   *
   * @param array $result
   *   Controller result.
   */
  protected function alterChooser(array &$result) {
    $keys = $this->moduleHandler->invokeAll('layout_builder_enhancer_allowed_block_keys');
    $this->moduleHandler->alter('layout_builder_enhancer_allowed_block_keys', $keys);
    foreach (Element::children($result) as $delta) {
      if (!in_array($delta, $keys)) {
        $result[$delta]['#access'] = FALSE;
      }
    }
    $this->moduleHandler->alter('layout_builder_enhancer_chooser_result', $result);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // Make sure we are before the main content view.
      KernelEvents::VIEW => ['onKernelView', 1],
    ];
  }

}

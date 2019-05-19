<?php

namespace Drupal\widget_api\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\widget_api\Plugin\Widget\WidgetPluginManagerInterface;

/**
 * Class WidgetBlock.
 */
class WidgetBlock extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The widget interface.
   *
   * @var \Drupal\widget_api\Plugin\Widget\WidgetPluginManagerInterface
   */
  private $widgetInterface;

  /**
   * Constructs new NodeBlock.
   */
  public function __construct(WidgetPluginManagerInterface $widgetInterface) {
    $this->widgetInterface = $widgetInterface;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.widget')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $widgets = $this->widgetInterface->getWidgetOptions();

    foreach ($widgets as $id => $widget) {
      $this->derivatives[$id] = $base_plugin_definition;
      $this->derivatives[$id]['admin_label'] = $widget;
      $this->derivatives[$id]['definition'] = $this->widgetInterface->getDefinition($id);
    }

    return $this->derivatives;
  }

}

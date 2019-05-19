<?php

namespace Drupal\stacks\TwigExtension;

use Drupal\stacks\Widget\WidgetData;
use Drupal\stacks\Entity\WidgetEntity;
use Drupal\stacks\Entity\WidgetInstanceEntity;

/**
 * Class EmbedWidgetInstance.
 * @package Drupal\stacks\TwigExtension
 *
 * Allows outputting a widget instance via a twig extension.
 */
class EmbedWidgetInstance extends \Twig_Extension {

  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('widget_instance_embed',
        [$this, 'output_widget_instance'],
        ['is_safe' => ['html']]
      ),
    ];
  }

  public function getName() {
    return 'widget_instance_embed';
  }

  public function output_widget_instance($widget_entity_id, $widget_instace_id) {
    $output = "<p>" . t("Widget instance doesn't exist.") . "</p>";

    $widget_data = new WidgetData();
    $node = \Drupal::routeMatch()->getParameter('node');
    $widget_instance_entity = WidgetInstanceEntity::load($widget_instace_id);
    $widget_entity = WidgetEntity::load($widget_entity_id);

    if ($node && $widget_instance_entity && $widget_entity) {
      $render_array = $widget_data->output($node, $widget_instance_entity, $widget_entity);
      $output = \Drupal::service('renderer')->render($render_array);
    }

    print $output;
  }

}

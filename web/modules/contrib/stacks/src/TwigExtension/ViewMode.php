<?php

namespace Drupal\stacks\TwigExtension;

use Drupal;

/**
 * Class ViewMode.
 * @package Drupal\stacks\TwigExtension
 */
class ViewMode extends \Twig_Extension {

  /**
   * Generates a list of all Twig filters that this extension defines.
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('view_mode', [$this, 'viewMode']),
      new \Twig_SimpleFilter('view_mode_object', [$this, 'viewModeNode']),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   */
  public function getName() {
    return 'stacks_viewmode.twig_extension';
  }

  public static function viewModeNode($entity, $view_mode) {
    if (!is_object($entity) || !method_exists($entity, 'getEntityTypeId')) {
      return;
    }

    $view_builder = \Drupal::entityTypeManager()
      ->getViewBuilder($entity->getEntityTypeId());
    $render_array = $view_builder->view($entity, $view_mode);
    return Drupal::service('renderer')->render($render_array);
  }

  /**
   * Takes a view mode (string), and an entity object. Returns rendered html.
   */
  public static function viewMode($entity_options, $view_mode) {
    if (!isset($entity_options['entity_type'])) {
      // This is not an entity object.
      return '';
    }

    $entity = Drupal::entityTypeManager()
      ->getStorage($entity_options['entity_type'])
      ->load($entity_options['entity_id']);

    $view_builder = Drupal::entityTypeManager()
      ->getViewBuilder($entity_options['entity_type']);
    $render_array = $view_builder->view($entity, $view_mode);
    return Drupal::service('renderer')->render($render_array);
  }

}

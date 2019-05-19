<?php

namespace Drupal\smallads_index\Plugin\Derivative;

use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create tabs under the main menu link, to views for each smallad type.
 */
class SmalladIndexSearchTasks extends DeriverBase implements ContainerDeriverInterface {

  protected $derivatives = [];
  protected $storage = [];

  /**
   * {@inheritdoc}
   */
  public function __construct($smallad_type_storage) {
    $this->storage = $smallad_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')->getStorage('smallad_type')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @note I had tried to put these tabs UNDER the smallad/offers tabs, but it
   * didn't work, because they both inherit from the same route and drupal
   * cannot deal with that.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->storage->loadMultiple() as $id => $type) {
      //default tab for the existing offers page
//      $this->derivatives["{$id}_local"] = [
//        'title' => "Local $id",
//        'description' => 'Search the local directory',
//        'route_name' => 'view.smallads_directory.page_list',
//        //'route_parameters' => ['smallad_type' => $id],
//        'parent_id' => 'smallads.autotasks:view.smallads_directory.page_list.'.$id,
//        'weight' => 1,
//        'id' => "{$id}_local"
//      ] + $base_plugin_definition;
//      //tab for global search
      $this->derivatives["{$id}_global"] = [
        'title' => "Global ".$type->label(),
        'description' => 'Search the global directory',
        'base_route' => 'view.smallads_directory.page_list',
        'route_name' => 'smallads_index.search',
        'route_parameters' => ['smallad_type' => $id],
        //'parent_id' => 'smallads.autotasks:view.smallads_directory.page_list.'.$id,
        'weight' => 5,
        'id' => "{$id}_global"
      ] + $base_plugin_definition;
    }
    return $this->derivatives;
  }

}

<?php

namespace Drupal\smallads\Plugin\Derivative;

use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create tabs under the main menu link, to views for each smallad type.
 */
class SmalladTasks extends DeriverBase implements ContainerDeriverInterface {

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
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $parent_id = 'view.smallads_directory.page_list';
    //A tab for each bundle under /smallads
    foreach ($this->storage->loadMultiple() as $id => $type) {
      $this->derivatives[$parent_id . '.' . $id] = [
        'title' => $type->label(),
        'base_route' => $parent_id,
        'route_name' => $parent_id,
        'route_parameters' => ['arg_0' => $id],
        'weight' => $type->getWeight(),
        'id' => $parent_id . '.' . $id
      ] + $base_plugin_definition;
    }
    return $this->derivatives;
  }

}

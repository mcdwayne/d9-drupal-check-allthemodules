<?php

namespace Drupal\smallads\Plugin\Derivative;

use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local actions to create smallads of each type.
 */
class SmalladActions extends DeriverBase implements ContainerDeriverInterface {

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
      $container->get('entity.manager')->getStorage('smallad_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->storage->loadMultiple() as $smallad_type) {
      $id = $smallad_type->id();
      $this->derivatives[$id.'.add_form.action'] = [
        'title' => 'Create ' . $smallad_type->label(),
        'route_name' => 'entity.smallad.add_form',
        'route_parameters' => ['smallad_type' => $id],
        'appears_on' => [
          'view.smallads_directory.collection',
          // Bit of a mess here because the view is supposed to override this
          'entity.smallad.collection'
        ],
      ];
    }
    return $this->derivatives;
  }

}

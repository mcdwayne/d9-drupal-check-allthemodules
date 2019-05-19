<?php

namespace Drupal\smallads\Plugin\Derivative;

use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A link to add each type of add in no particular order.
 */
class SmalladMenuLink extends DeriverBase implements ContainerDeriverInterface {

  protected $derivatives = [];
  protected $smallAdTypeStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct($smallAdTypeStorage) {
    $this->smallAdTypeStorage = $smallAdTypeStorage;
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
    foreach ($this->smallAdTypeStorage->loadMultiple() as $id => $type) {
      // $type seems to be untranslated for some reason
      $this->derivatives[$id.".add_form.link"] = [
        'title' => t('Add @type', ['@type' => strtolower($type->label())]),
        'route_name' => 'entity.smallad.add_form',
        'route_parameters' => ['smallad_type' => $id],
        'provider' => 'smallads',
      // @todo find somewhere better to put these
        'menu_name' => 'account',
        'weight' => $type->getWeight(),
      ];
    }
    // @todo might be nice to have some translated aliases for this
    return $this->derivatives;
  }

}

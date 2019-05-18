<?php

namespace Drupal\altruja\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class AltrujaBlock extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The altruja block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $altrujaBlockStorage;

  /**
   * Constructs an AltrujaBlock object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $$altruja_block_storage
   *   The altruja block storage.
   */
  public function __construct(EntityStorageInterface $altruja_block_storage) {
    $this->altrujaBlockStorage = $altruja_block_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('altruja_block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    $blocks = $this->altrujaBlockStorage->loadMultiple();
    foreach ($blocks as $block) {
      $this->derivatives[$block->uuid()] = $base_plugin_definition;
      $this->derivatives[$block->uuid()]['admin_label'] = $block->label();
    }
    return $this->derivatives;
  }

}

<?php

namespace Drupal\blockgroup\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for blockgroup blocks.
 *
 * @see \Drupal\blockgroup\Plugin\Block\BlockGroup.
 */
class BlockGroups extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The custom block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockGroupStorage;

  /**
   * Constructs a BlockContent object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_groups_storage
   *   The custom block storage.
   */
  public function __construct(EntityStorageInterface $block_groups_storage) {
    $this->blockGroupStorage = $block_groups_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $entity_manager = $container->get('entity_type.manager');
    return new static(
      $entity_manager->getStorage('block_group_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $block_groups = $this->blockGroupStorage->loadMultiple();
    foreach ($block_groups as $block_group) {
      $this->derivatives[$block_group->id()] = $base_plugin_definition;
      $this->derivatives[$block_group->id()]['admin_label'] = $block_group->label();
      $this->derivatives[$block_group->id()]['config_dependencies'][$block_group->getConfigDependencyKey()] = [
        $block_group->getConfigDependencyName(),
      ];
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}

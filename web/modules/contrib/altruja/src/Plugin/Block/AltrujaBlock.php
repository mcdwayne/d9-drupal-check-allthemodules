<?php

namespace Drupal\altruja\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an altruja block.
 *
 * @Block(
 *   id = "altruja_block",
 *   admin_label = @Translation("Altruja block"),
 *   category = @Translation("Altruja"),
 *   deriver = "Drupal\altruja\Plugin\Derivative\AltrujaBlock"
 * )
 */
class AltrujaBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new BlockContentBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * Build the content for mymodule block.
   */
  public function build() {
    $block = $this->getEntity();
    return [
      '#type' => 'inline_template',
      '#template' => '<!--' . $block->getPlaceholder() . '-->',
    ];
  }

  /**
   * Loads the block content entity of the block.
   *
   * @return \Drupal\block_content\BlockContentInterface|null
   *   The block content entity.
   */
  protected function getEntity() {
    $uuid = $this->getDerivativeId();
    $block = $this->entityManager->loadEntityByUuid('altruja_block', $uuid);
    return $block;
  }

}

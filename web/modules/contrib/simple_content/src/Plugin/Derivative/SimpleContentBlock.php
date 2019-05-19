<?php

namespace Drupal\simple_content\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieves block plugin definitions for all simple content types.
 */
class SimpleContentBlock extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The simple content type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $SimpleContentTypeStorage;

  /**
   * Constructs a CustomContentBlock object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_storage
   *   The custom block storage.
   */
  public function __construct(EntityStorageInterface $block_content_storage) {
    $this->SimpleContentTypeStorage = $block_content_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('simple_content_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $simple_content_types = $this->SimpleContentTypeStorage->loadMultiple();
    $this->derivatives = [];
    /** @var $simple_content_type \Drupal\simple_content\Entity\SimpleContentType */
    foreach ($simple_content_types as $simple_content_type) {
      $this->derivatives[$simple_content_type->id()] = $base_plugin_definition;
      $this->derivatives[$simple_content_type->id()]['admin_label'] = $simple_content_type->label();
      $this->derivatives[$simple_content_type->id()]['config_dependencies']['content'] = [
        $simple_content_type->getConfigDependencyName()
      ];
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}

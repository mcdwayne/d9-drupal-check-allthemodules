<?php

namespace Drupal\entity_gallery\Plugin\views\argument;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\views\Plugin\views\argument\StringArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept an entity gallery type.
 *
 * @ViewsArgument("entity_gallery_type")
 */
class Type extends StringArgument {

  /**
   * EntityGalleryType storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityGalleryTypeStorage;

  /**
   * Constructs a new Entity Gallery Type object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $entity_gallery_type_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityGalleryTypeStorage = $entity_gallery_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_manager->getStorage('entity_gallery_type')
    );
  }

  /**
   * Override the behavior of summaryName(). Get the user friendly version
   * of the entity gallery type.
   */
  public function summaryName($data) {
    return $this->entity_gallery_type($data->{$this->name_alias});
  }

  /**
   * Override the behavior of title(). Get the user friendly version of the
   * entity gallery type.
   */
  function title() {
    return $this->entity_gallery_type($this->argument);
  }

  function entity_gallery_type($type_name) {
    $type = $this->entityGalleryTypeStorage->load($type_name);
    $output = $type ? $type->label() : $this->t('Unknown entity gallery type');
    return $output;
  }

}

<?php

namespace Drupal\entity_gallery\Plugin\views\argument;

use Drupal\entity_gallery\EntityGalleryStorageInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept an entity gallery id.
 *
 * @ViewsArgument("entity_gallery_egid")
 */
class Egid extends NumericArgument {

  /**
   * The entity gallery storage.
   *
   * @var \Drupal\entity_gallery\EntityGalleryStorageInterface
   */
  protected $entityGalleryStorage;

  /**
   * Constructs the Egid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param EntityGalleryStorageInterface $entity_gallery_storage
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityGalleryStorageInterface $entity_gallery_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityGalleryStorage = $entity_gallery_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('entity_gallery')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the entity gallery.
   */
  public function titleQuery() {
    $titles = array();

    $entity_galleries = $this->entityGalleryStorage->loadMultiple($this->value);
    foreach ($entity_galleries as $entity_gallery) {
      $titles[] = $entity_gallery->label();
    }
    return $titles;
  }

}

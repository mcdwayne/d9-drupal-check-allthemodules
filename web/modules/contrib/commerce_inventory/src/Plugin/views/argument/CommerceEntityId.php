<?php

namespace Drupal\commerce_inventory\Plugin\views\argument;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler for mapping commerce entity IDs to their labels.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("commerce_inventory_entity_id")
 */
class CommerceEntityId extends NumericArgument implements ContainerFactoryPluginInterface {

  /**
   * The current entity type id.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!is_null($this->entityTypeId)) {
      $entity_type_id = $this->entityTypeId;
    }
    elseif (isset($configuration['field entity_type'])) {
      $entity_type_id = $configuration['field entity_type'];
    }
    elseif (isset($configuration['entity_type'])) {
      $entity_type_id = $configuration['entity_type'];
    }
    else {
      $entity_type_id = '';
    }
    $this->storage = $entity_type_manager->getStorage($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Override the behavior of label(). Get the title of the entity.
   */
  public function title() {
    // There might be no valid argument.
    if ($this->argument) {
      $entity = $this->storage->load($this->argument);
      if (!empty($entity)) {
        return $entity->label();
      }
    }
    // TODO review text.
    return $this->t('No name');
  }

}

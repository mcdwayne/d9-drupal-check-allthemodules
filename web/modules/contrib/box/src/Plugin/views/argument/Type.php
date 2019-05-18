<?php

namespace Drupal\box\Plugin\views\argument;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\views\Plugin\views\argument\StringArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a box type.
 *
 * @ViewsArgument("box_type")
 */
class Type extends StringArgument {

  /**
   * BoxType storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $boxTypeStorage;

  /**
   * Constructs a new Box Type object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $box_type_storage
   *   The entity storage class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $box_type_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->boxTypeStorage = $box_type_storage;
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
      $entity_manager->getStorage('box_type')
    );
  }

  /**
   * Override the behavior of summaryName(). Get the user friendly version of the box type.
   */
  public function summaryName($data) {
    return $this->boxType($data->{$this->name_alias});
  }

  /**
   * Override the behavior of title(). Get the user friendly version of the box type.
   */
  public function title() {
    return $this->boxType($this->argument);
  }

  public function boxType($type_name) {
    $type = $this->boxTypeStorage->load($type_name);
    $output = $type ? $type->label() : $this->t('Unknown box type');
    return $output;
  }

}

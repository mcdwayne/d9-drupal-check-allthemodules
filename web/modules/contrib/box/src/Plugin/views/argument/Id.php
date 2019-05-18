<?php

namespace Drupal\box\Plugin\views\argument;

use Drupal\box\BoxStorageInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a box id.
 *
 * @ViewsArgument("box_id")
 */
class Id extends NumericArgument {

  /**
   * The box storage.
   *
   * @var \Drupal\box\BoxStorageInterface
   */
  protected $boxStorage;

  /**
   * Constructs the Id object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\box\BoxStorageInterface $box_storage
   *   The box storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BoxStorageInterface $box_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->boxStorage = $box_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('box')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the box.
   */
  public function titleQuery() {
    $titles = [];

    $boxes = $this->boxStorage->loadMultiple($this->value);
    foreach ($boxes as $box) {
      $titles[] = $box->label();
    }
    return $titles;
  }

}

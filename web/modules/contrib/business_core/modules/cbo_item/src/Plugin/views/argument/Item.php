<?php

namespace Drupal\cbo_item\Plugin\views\argument;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\cbo_item\ItemStorageInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler for item pid.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("item")
 */
class Item extends NumericArgument implements ContainerFactoryPluginInterface {

  /**
   * Item storage handler.
   *
   * @var \Drupal\cbo_item\ItemStorageInterface
   */
  protected $itemStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ItemStorageInterface $item_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->itemStorage = $item_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('item')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the item.
   */
  public function title() {
    // There might be no valid argument.
    if ($this->argument) {
      $item = $this->itemStorage->load($this->argument);
      if (!empty($item)) {
        return $item->label();
      }
    }
    return $this->t('No name');
  }

}

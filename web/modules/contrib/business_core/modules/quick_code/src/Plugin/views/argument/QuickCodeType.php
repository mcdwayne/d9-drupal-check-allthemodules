<?php

namespace Drupal\quick_code\Plugin\views\argument;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\argument\StringArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept quick_code_type.
 *
 * @ViewsArgument("quick_code_type")
 */
class QuickCodeType extends StringArgument implements ContainerFactoryPluginInterface {

  /**
   * QuickCodeType storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $quickCodeTypeStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $quick_code_type_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->quickCodeTypeStorage = $quick_code_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('quick_code_type')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the node.
   */
  public function title() {
    // There might be no valid argument.
    if ($this->argument) {
      $quick_code_type = $this->quickCodeTypeStorage->load($this->argument);
      if (!empty($quick_code_type)) {
        return $quick_code_type->label();
      }
    }
    return $this->t('No name');
  }

}

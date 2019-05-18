<?php

namespace Drupal\quick_code\Plugin\views\argument;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\quick_code\QuickCodeStorageInterface;
use Drupal\views\Plugin\views\argument\StringArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept quick_code.
 *
 * @ViewsArgument("quick_code")
 */
class QuickCode extends StringArgument implements ContainerFactoryPluginInterface {

  /**
   * QuickCode storage handler.
   *
   * @var \Drupal\quick_code\QuickCodeStorageInterface
   */
  protected $quickCodeStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QuickCodeStorageInterface $quick_code_type_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->quickCodeStorage = $quick_code_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('quick_code')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the node.
   */
  public function title() {
    // There might be no valid argument.
    if ($this->argument) {
      $quick_code_type = $this->quickCodeType->load($this->argument);
      if (!empty($quick_code_type)) {
        return $quick_code_type->label();
      }
    }
    return $this->t('No name');
  }

}

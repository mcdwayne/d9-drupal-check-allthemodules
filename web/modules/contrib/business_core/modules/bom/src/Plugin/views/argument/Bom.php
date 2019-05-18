<?php

namespace Drupal\bom\Plugin\views\argument;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\bom\BomStorageInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler for bom oid.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("bom")
 */
class Bom extends NumericArgument implements ContainerFactoryPluginInterface {

  /**
   * Bom storage handler.
   *
   * @var \Drupal\bom\BomStorageInterface
   */
  protected $bomStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BomStorageInterface $bom_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->bomStorage = $bom_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('bom')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the node.
   */
  public function title() {
    // There might be no valid argument.
    if ($this->argument) {
      $bom = $this->bomStorage->load($this->argument);
      if (!empty($bom)) {
        return $bom->label();
      }
    }
    return $this->t('No name');
  }

}

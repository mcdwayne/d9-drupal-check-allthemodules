<?php

namespace Drupal\webform_composite\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides webform element reusable custom webform composite elements.
 *
 * @see \Drupal\webform_composite\Plugin\WebformElement\WebformReusableComposite
 */
class WebformCompositeDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The Reusable Composite storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $compositeStorage;

  /**
   * Constructs new WebformReusableCompositeDeriver.
   *
   * @param Drupal\Core\Entity\EntityStorageInterface $composite_storage
   *   The Dynamic Composite storage.
   */
  public function __construct(EntityStorageInterface $composite_storage) {
    $this->compositeStorage = $composite_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')->getStorage('webform_composite')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $composites = $this->compositeStorage->loadMultiple();
    foreach ($composites as $composite) {
      $this->derivatives[$composite->id()] = $base_plugin_definition;
      $this->derivatives[$composite->id()]['label'] = $composite->label();
    }
    return $this->derivatives;
  }

}

<?php

namespace Drupal\ad_entity\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Deriver class for Display blocks for Advertisement.
 */
class AdDisplayBlock extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The storage of Display configs for Advertisement.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $adDisplayStorage;

  /**
   * Constructs a new deriver for Advertising display blocks.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $ad_display_storage
   *   The storage of Display configs for Advertisement.
   */
  public function __construct(EntityStorageInterface $ad_display_storage) {
    $this->adDisplayStorage = $ad_display_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')->getStorage('ad_display')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->adDisplayStorage->loadMultiple() as $id => $ad_display) {
      $this->derivatives[$id] = $base_plugin_definition;
      $this->derivatives[$id]['admin_label'] = $ad_display->label();
      $this->derivatives[$id]['config_dependencies']['config'] = [$ad_display->getConfigDependencyName()];
    }
    return $this->derivatives;
  }

}

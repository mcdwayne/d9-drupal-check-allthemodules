<?php

namespace Drupal\breadcrumb_manager\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Breadcrumb title resolver plugins.
 */
abstract class BreadcrumbTitleResolverBase extends PluginBase implements BreadcrumbTitleResolverInterface, ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  protected $isActive;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    if (!isset($this->isActive)) {
      $this->setActive();
    }
    return $this->isActive;
  }

  /**
   * {@inheritdoc}
   */
  public function setActive($active = TRUE) {
    $this->isActive = (bool) $active;
  }

}

<?php

namespace Drupal\domain_menu_access\Plugin\Derivative;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\system\Plugin\Derivative\SystemMenuBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for domain access menus.
 *
 * @see \Drupal\domain_access\Plugin\Block\DomainMenuAccessMenuBlock
 */
class DomainMenuAccessMenuBlock extends SystemMenuBlock {

  /**
   * Domain settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs new SystemMenuBlock.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $menu_storage
   *   The menu storage.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Domain access settings.
   */
  public function __construct(EntityStorageInterface $menu_storage, ImmutableConfig $config) {
    parent::__construct($menu_storage);

    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')->getStorage('menu'),
      $container->get('config.factory')->get('domain_menu_access.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $menu_enabled = $this->config->get('menu_enabled');

    foreach ($this->menuStorage->loadMultiple() as $menu => $entity) {
      if (in_array($menu, $menu_enabled)) {
        $this->derivatives[$menu] = $base_plugin_definition;
        $this->derivatives[$menu]['admin_label'] = $entity->label();
        $this->derivatives[$menu]['config_dependencies']['config'] = [$entity->getConfigDependencyName()];
      }
    }

    return $this->derivatives;
  }

}

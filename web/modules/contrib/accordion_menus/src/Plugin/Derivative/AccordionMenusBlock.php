<?php

namespace Drupal\accordion_menus\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for accordion menus.
 *
 * @see \Drupal\accordion_menus\Plugin\Block\AccordionMenusBlock
 */
class AccordionMenusBlock extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The menu storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuStorage;

  /**
   * Constructs new SystemMenuBlock.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $menu_storage
   *   The menu storage.
   */
  public function __construct(EntityStorageInterface $menu_storage) {
    $this->menuStorage = $menu_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')->getStorage('menu')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Get menus from accordion configuration.
    $accordion_menus = \Drupal::config('accordion_menus.settings')->get('accordion_menus');
    if (!empty($accordion_menus)) {
      foreach ($this->menuStorage->loadMultiple() as $menu => $entity) {
        if (in_array($menu, $accordion_menus, TRUE)) {
          $this->derivatives[$menu] = $base_plugin_definition;
          $this->derivatives[$menu]['admin_label'] = t('Accordion') . ' ' . $entity->label();
          $this->derivatives[$menu]['config_dependencies']['config'] = [$entity->getConfigDependencyName()];
        }
      }
    }

    return $this->derivatives;
  }

}

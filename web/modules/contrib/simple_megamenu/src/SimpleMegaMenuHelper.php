<?php

namespace Drupal\simple_megamenu;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simple_megamenu\Entity\SimpleMegaMenuTypeInterface;

/**
 * Class SimpleMegaMenuHelper.
 *
 * @package Drupal\simple_megamenu
 */
class SimpleMegaMenuHelper implements SimpleMegaMenuHelperInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetMenus(SimpleMegaMenuTypeInterface $entity) {
    return array_filter($entity->getTargetMenu());
  }

  /**
   * {@inheritdoc}
   */
  public function getMegaMenuTypeWhichTargetMenu($menu_name) {
    $mega_menu_types = [];
    $simple_mega_menu_types = $this->entityTypeManager->getStorage('simple_mega_menu_type')->loadMultiple();
    /** @var \Drupal\simple_megamenu\Entity\SimpleMegaMenuType $entity */
    foreach ($simple_mega_menu_types as $id => $entity) {
      $target_menus = $this->getTargetMenus($entity);
      if (in_array($menu_name, $target_menus)) {
        $mega_menu_types[$id] = $entity->label();
      }
    }
    return $mega_menu_types;
  }

  /**
   * {@inheritdoc}
   */
  public function menuIsTargetedByMegaMenuType($menu_name) {
    $simple_mega_menu_types = $this->entityTypeManager->getStorage('simple_mega_menu_type')->loadMultiple();
    /** @var \Drupal\simple_megamenu\Entity\SimpleMegaMenuType $entity */
    foreach ($simple_mega_menu_types as $id => $entity) {
      $target_menus = $this->getTargetMenus($entity);
      if (in_array($menu_name, $target_menus)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSimpleMegaMenuType($id) {
    $simpleMegaMenuType = $this->entityTypeManager->getStorage('simple_mega_menu_type')->load($id);
    return $simpleMegaMenuType;
  }

  /**
   * {@inheritdoc}
   */
  public function getSimpleMegaMenu($id) {
    $simpleMegaMenu = $this->entityTypeManager->getStorage('simple_mega_menu')->load($id);
    return $simpleMegaMenu;
  }

}

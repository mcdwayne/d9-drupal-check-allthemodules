<?php

namespace Drupal\tb_megamenu\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\system\Entity\Menu;

/**
 * Provides a listing of MegaMenuConfig entities.
 */
class MegaMenuList extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['menu'] = $this->t('Menu Name');
    $header['label'] = $this->t('Menu Title');
    $header['theme'] = $this->t('Theme Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $menu_info = Menu::load($entity->menu);
    $row['menu'] = $entity->menu;
    $row['label'] = $menu_info !== NULL ? $menu_info->label() : "MISSING MENU! Was it deleted?";
    $row['theme'] = $entity->theme;
    return $row + parent::buildRow($entity);
  }

}

<?php

namespace Drupal\simple_megamenu_bonus\TwigExtension;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface;

/**
 * Class SimpleMegaMenuBonusTwigExtension.
 *
 * @package Drupal\simple_megamenu_bonus
 */
class SimpleMegaMenuBonusTwigExtension extends \Drupal\simple_megamenu\TwigExtension\SimpleMegaMenuTwigExtension {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs \Drupal\Core\Template\TwigExtension.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return array_merge(parent::getFunctions(), [
      new \Twig_SimpleFunction('view_megamenu_bonus', [$this, 'viewMegaMenuBonus']),
    ]);
  }

  /**
   * Render a mega menu in a view from an Url object.
   * In contrast to viewMegaMenu the view_mode is being determined from the menu item setting.
   *
   * @param \Drupal\Core\Url|string $url
   *   The URL object used for the link.
   *
   * @return array
   *   A render array representing a the megamenu for the given view mode.
   */
  public function viewMegaMenuBonus(array $menu_item, $menu_item_below_subtree_rendered) {
    $url = $menu_item['url'];
    if (!$url instanceof Url) {
      return [];
    }
    if (!$this->hasMegaMenu($url)) {
      return [];
    }
    $build = [];
    $menu_attributes = $url->getOption('attributes');
    $simple_mega_menu_id = $menu_attributes['data-simple-mega-menu'];
    /** @var \Drupal\simple_megamenu\Entity\SimpleMegaMenu $simple_mega_menu */
    $simple_mega_menu = $this->entityTypeManager->getStorage('simple_mega_menu')->load($simple_mega_menu_id);
    if ($simple_mega_menu instanceof SimpleMegaMenuInterface) {
      if (!$simple_mega_menu->access('view')) {
        return $build;
      }
      $viewBuilder = $this->entityTypeManager->getViewBuilder($simple_mega_menu->getEntityTypeId());

      // Provide the submenu item tree
      $simple_mega_menu->set('menu_item_below_subtree', $menu_item_below_subtree_rendered);

      $view_mode = 'default';
      $render_view_mode_value = $simple_mega_menu->get('render_view_mode')->getValue();
      if(!empty($render_view_mode_value[0]['value'])){
        $view_mode = $render_view_mode_value[0]['value'];
      }
      $build = $viewBuilder->view($simple_mega_menu, $view_mode);
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'simple_megamenu_bonus.twig.extension';
  }

}

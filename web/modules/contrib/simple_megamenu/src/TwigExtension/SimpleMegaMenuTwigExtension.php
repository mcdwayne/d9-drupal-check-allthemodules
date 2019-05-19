<?php

namespace Drupal\simple_megamenu\TwigExtension;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface;

/**
 * Class SimpleMegaMenuTwigExtension.
 *
 * @package Drupal\simple_megamenu
 */
class SimpleMegaMenuTwigExtension extends \Twig_Extension {

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
    return [
      new \Twig_SimpleFunction('view_megamenu', [$this, 'viewMegaMenu']),
      new \Twig_SimpleFunction('has_megamenu', [$this, 'hasMegaMenu']),
    ];
  }

  /**
   * Render a mega menu in a view from an Url object.
   *
   * @param \Drupal\Core\Url|string $url
   *   The URL object used for the link.
   * @param string $view_mode
   *   The view mode to use for rendering the mega menu.
   *
   * @return array
   *   A render array representing a the megamenu for the given view mode.
   */
  public function viewMegaMenu(Url $url, $view_mode = 'default') {
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
      $build = $viewBuilder->view($simple_mega_menu, $view_mode);
    }

    return $build;
  }

  /**
   * Check if a mega menu is referenced by an Url object.
   *
   * @param \Drupal\Core\Url|string $url
   *   The URL object used for the link.
   *
   * @return bool
   *   TRUE if a simple mega menu entity is reference by the Url object.
   */
  public function hasMegaMenu(Url $url) {
    if (!$url instanceof Url) {
      return FALSE;
    }
    $menu_attributes = $url->getOption('attributes');
    if (isset($menu_attributes['data-simple-mega-menu']) && !empty($menu_attributes['data-simple-mega-menu'])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'simple_megamenu.twig.extension';
  }

}

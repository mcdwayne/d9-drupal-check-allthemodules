<?php

namespace Drupal\tb_megamenu\Controller;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;
use Drupal\tb_megamenu\Entity\MegaMenuConfig;
use Drupal\tb_megamenu\TBMegaMenuBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Menu\MenuLinkTree;
use Drupal\Core\Render\RendererInterface;
use Drupal\Component\Serialization\Json;

/**
 * Handler for configuring and saving MegaMenu settings.
 */
class TBMegaMenuAdminController extends ControllerBase {

  /**
   * The menu tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTree
   */
  protected $menuTree;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a TBMegaMenuAdminController object.
   *
   * @param \Drupal\Core\Menu\MenuLinkTree $menu_tree
   *   The Menu Link Tree service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(MenuLinkTree $menu_tree, RendererInterface $renderer) {
    $this->menuTree = $menu_tree;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('menu.link_tree'),
        $container->get('renderer')
        );
  }

  /**
   * Ajax callback for admin screen.
   *
   * Handles:  Save, Reset, and add block requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A string response with either a success/error message or just data.
   */
  public function saveConfiguration(Request $request) {
    $data = NULL;
    $action = '';
    $result = 'Invalid TB Megamenu Ajax request!';

    // All ajax calls should use json data now.
    if ($request->getContentType() == 'json') {
      $data = Json::decode($request->getContent());
      $action = $data['action'];
    }
    switch ($action) {
      case 'load':
        $menu_name = isset($data['menu_name']) ? $data['menu_name'] : NULL;
        $theme = isset($data['theme']) ? $data['theme'] : NULL;
        if ($menu_name && $theme) {
          $renderable_array = TBMegaMenuBuilder::renderBlock($menu_name, $theme);
          $result = $this->renderer
            ->render($renderable_array)
            ->__toString();
        }
        break;

      case 'save':
        $menu_config = isset($data['menu_config']) ? $data['menu_config'] : NULL;
        $block_config = isset($data['block_config']) ? $data['block_config'] : NULL;
        $menu_name = isset($data['menu_name']) ? $data['menu_name'] : NULL;
        $theme = isset($data['theme']) ? $data['theme'] : NULL;
        if ($menu_config && $menu_name && $block_config && $theme) {
          // This is parameter to load menu_tree with the enabled links.
          $menu_tree_parameters = (new MenuTreeParameters)->onlyEnabledLinks();
          // Load menu items with condition.
          $menu_items = $this->menuTree->load($menu_name, $menu_tree_parameters);
          // Sync mega menu before store.
          TBMegaMenuBuilder::syncConfigAll($menu_items, $menu_config, 'backend');
          TBMegaMenuBuilder::syncOrderMenus($menu_config);

          $config = MegaMenuConfig::loadMenu($menu_name, $theme);
          if ($config === NULL) {
            $msg = $this->t("Cannot create a new config object in save!");
            drupal_set_message($msg);
            $result = $msg;
            break;
          }
          $config->setBlockConfig($block_config);
          $config->setMenuConfig($menu_config);
          $rc = $config->save();
          if ($rc == 1 || $rc == 2) {
            $result = $this->t("Saved config sucessfully!");
          }
          else {
            $result = $this->t("Error saving config!");
          }
        }
        else {
          $problem = ($menu_name ? '' : "menu_name ") . ($theme ? '' : "theme_name ") .
            ($block_config ? '' : "block_config ") . ($menu_config ? '' : "menu_config");
          $msg = $this->t(
            "Error Saving TB MegaMenu configuration: Post was missing the following information: @problem",
            ['@problem' => $problem]);
          drupal_set_message($msg);
          $result = $msg;
        }
        break;

      case 'load_block':
        $block_id = isset($data['block_id']) ? $data['block_id'] : NULL;
        $id = isset($data['id']) ? $data['id'] : NULL;
        $showblocktitle = isset($data['showblocktitle']) ? $data['showblocktitle'] : NULL;
        if ($block_id && $id) {
          $render = [
            '#theme' => 'tb_megamenu_block',
            '#block_id' => $block_id,
            '#section' => 'backend',
            '#showblocktitle' => $showblocktitle,
          ];
          $content = $this->renderer
            ->render($render)
            ->__toString();
          $result = Json::encode(['content' => $content, 'id' => $id]);
        }
        break;

      default:
        break;
    }

    return new Response($result);
  }

  /**
   * This is a menu page. To edit Mega Menu.
   */
  public function configMegaMenu(ConfigEntityInterface $tb_megamenu, Request $request) {
    // Add font-awesome library.
    $page['#attached']['library'][] = 'tb_megamenu/form.font-awesome';
    // Add chosen library.
    $page['#attached']['library'][] = 'tb_megamenu/form.chosen';
    // Add a custom library.
    $page['#attached']['library'][] = 'tb_megamenu/form.configure-megamenu';
    URL::fromRoute('tb_megamenu.admin.save', [], ['absolute' => TRUE]);

    $abs_url_config = URL::fromRoute('tb_megamenu.admin.save', [], ['absolute' => TRUE])->toString();
    $page['#attached']['drupalSettings']['TBMegaMenu']['saveConfigURL'] = $abs_url_config;
    if (!empty($tb_megamenu)) {
      $page['tb_megamenu'] = [
        '#theme' => 'tb_megamenu_backend',
        '#menu_name' => $tb_megamenu->menu,
        '#block_theme' => $tb_megamenu->theme,
      ];
    }
    return $page;
  }

}

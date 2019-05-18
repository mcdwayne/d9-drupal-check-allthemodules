<?php

/**
 * @file
 * Contains \Drupal\jstree_menu\Plugin\Block\JsTreeMenuBlock.
 */

namespace Drupal\jstree_menu\Plugin\Block;

use Drupal\system\Entity\Menu;
use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides jstree menu block.
 *
 * @Block(
 *   id = "js_tree_menu_block",
 *   admin_label = @Translation("jsTree menu"),
 *   category = @Translation("Blocks")
 * )
 */
class JsTreeMenuBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block_config = $this->getConfiguration();
    $config = \Drupal::config('jstree_menu.config');

    $level = 4;
    $menu_name = !empty($block_config['menu']) ? $block_config['menu'] : 'main';

    $menu_tree = \Drupal::menuTree();
    $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
    $parameters->setMaxDepth($level + 1);
    $tree = $menu_tree->load($menu_name, $parameters);

    if (is_array($tree) && count($tree) > 0) {

      $manipulators = array(
        array('callable' => 'menu.default_tree_manipulators:checkAccess'),
        array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
      );
      $tree = $menu_tree->transform($tree, $manipulators);
      $menu = $menu_tree->build($tree);

      $theme = $config->get('jstree_menu_theme');
      if ($theme == 'default') {
        $libraries = array('jstree_menu/jstree', 'jstree_menu/jstree_menu');
      }
      else {
        $libraries = array('jstree_menu/jstree', 'jstree_menu/jstree_proton', 'jstree_menu/jstree_menu');
      }

      // Call to custom twig template.
      return array(
        '#theme' => 'jstree_menu_menu',
        '#menu_name' => $menu['#menu_name'],
        '#items' => $menu['#items'],
        '#id' => $menu['#menu_name'],
        '#icon' => $config->get('jstree_menu_icon'),
        '#icon_leaves' => $config->get('jstree_menu_icon_leaves'),
        '#attached' => array(
          'library' =>  $libraries,
          // Pass variables to JS.
          'drupalSettings' => array(
            'jstree_menu' => array(
              'theme' => $theme,
              'rem_border' => $config->get('jstree_menu_remove_border'),
              'height' => $config->get('jstree_menu_height'),
            )
          ),
        ),
        // https://www.drupal.org/docs/8/api/cache-api/cache-contexts
        '#cache' => array(
          'contexts' => array('url.path'),
        ),
      );

    }
    else {
      $markup = '<p>' . $this->t('Please, make sure @menu menu has links.', array('@menu' => $menu_name)) . '</p>';
    }

    $build = array(
      '#type' => 'markup',
      '#markup' => $markup,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['js_tree_menu_block_menu'] = array(
      '#type' => 'select',
      '#title' => $this->t('Menu'),
      '#options' => menu_ui_get_menus(),
      '#description' => $this->t('Select the menu you want to render with jsTree library'),
      '#default_value' => isset($config['menu']) ? $config['menu'] : '',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['menu'] = $form_state->getValue('js_tree_menu_block_menu');
  }
}

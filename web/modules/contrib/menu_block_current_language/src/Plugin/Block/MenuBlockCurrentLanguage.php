<?php

namespace Drupal\menu_block_current_language\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\system\Plugin\Block\SystemMenuBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a generic Menu block.
 *
 * @Block(
 *   id = "menu_block_current_language",
 *   admin_label = @Translation("Menu block current language: Menu"),
 *   category = @Translation("Menu block current language"),
 *   deriver = "Drupal\system\Plugin\Derivative\SystemMenuBlock"
 * )
 */
class MenuBlockCurrentLanguage extends SystemMenuBlock {

  /**
   * The menu active trail.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\menu_block_current_language\Plugin\Block\MenuBlockCurrentLanguage $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    // This allow us to instantiate this without calling the parent constructor.
    $instance->setMenuActiveTrail($container->get('menu.active_trail'));

    return $instance;
  }

  /**
   * Sets the menu active trail.
   *
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $activeTrail
   *   The active menu trail.
   *
   * @return $this
   *   The self.
   */
  public function setMenuActiveTrail(MenuActiveTrailInterface $activeTrail) {
    $this->menuActiveTrail = $activeTrail;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu_name = $this->getDerivativeId();

    // @note: Requires patch from #2594425.
    if (!empty($this->configuration['expand_all_items'])) {
      $parameters = new MenuTreeParameters();
      $active_trail = $this->menuActiveTrail->getActiveTrailIds($menu_name);
      $parameters->setActiveTrail($active_trail);
    }
    else {
      $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
    }
    // Adjust the menu tree parameters based on the block's configuration.
    $level = $this->configuration['level'];
    $depth = $this->configuration['depth'];
    $parameters->setMinDepth($level);
    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));
    }

    // For menu blocks with start level greater than 1, only show menu items
    // from the current active trail. Adjust the root according to the current
    // position in the menu in order to determine if we can show the subtree.
    // @see #2631468.
    if ($level > 1) {
      if (count($parameters->activeTrail) >= $level) {
        // Active trail array is child-first. Reverse it, and pull the new menu
        // root based on the parent of the configured start level.
        $menu_trail_ids = array_reverse(array_values($parameters->activeTrail));
        $menu_root = $menu_trail_ids[$level - 1];
        $parameters->setRoot($menu_root)->setMinDepth(1);
        if ($depth > 0) {
          $parameters->setMaxDepth(min($level - 1 + $depth - 1, $this->menuTree->maxDepth()));
        }
      }
      else {
        return [];
      }
    }

    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      [
        'callable' => 'menu_block_current_language_tree_manipulator::filterLanguages',
        'args' => [$this->configuration['translation_providers']],
      ],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);

    return $this->menuTree->build($tree);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['translation_providers'] = $form_state->getValue('translation_providers');
    parent::blockSubmit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['translation_providers'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled Core link types'),
      '#options' => [
        'menu_link_content' => $this->t('Menu link content'),
        'views' => $this->t('Views'),
        'default' => $this->t('String translation (Experimental)'),
      ],
      '#default_value' => $this->configuration['translation_providers'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Translate views and menu link content links by default.
    $config = [
      'translation_providers' => [
        'views' => 'views',
        'menu_link_content' => 'menu_link_content',
        'default' => 0,
      ],
    ];
    return $config + parent::defaultConfiguration();
  }

}

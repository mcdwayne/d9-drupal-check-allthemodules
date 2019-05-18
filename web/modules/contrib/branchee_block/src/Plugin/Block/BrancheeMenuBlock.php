<?php

namespace Drupal\branchee_block\Plugin\Block;

/**
 * @file
 * Contains Drupal\branchee_block\Plugin\Block\BrancheeMenuBlock.
 */

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a 'Branchee Menu' Block.
 *
 * @Block(
 *   id = "branchee_menu_block",
 *   admin_label = @Translation("Branchee Menu Block"),
 * )
 */
class BrancheeMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The menu link tree service.
   *
   * @var MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MenuLinkTreeInterface $menu_link_tree) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuLinkTree = $menu_link_tree;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.link_tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    // If no menu was selected, don't try to render one.
    if (!empty($config['menu'])) {
      $menu = $config['menu'];

      // Build a default set of menuTreeParameters.
      $parameters = new MenuTreeParameters();
      $parameters->onlyEnabledLinks();

      // Load the menu tree using the parameters defined.
      $menu_tree = \Drupal::menuTree();

      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ];

      $level = $config['level'];
      $depth = $config['depth'];
      $parameters->setMinDepth($level);
      // When the depth is configured to zero, there is no depth limit. When depth
      // is non-zero, it indicates the number of levels that must be displayed.
      // Hence this is a relative depth that we must convert to an actual
      // (absolute) depth, that may never exceed the maximum depth.
      if ($depth > 0) {
        $parameters->setMaxDepth(min($level + $depth - 1, $menu_tree->maxDepth()));
      }

      // For menu blocks with start level greater than 1, only show menu items
      // from the current active trail. Adjust the root according to the current
      // position in the menu in order to determine if we can show the subtree.
      if ($level > 1) {
        if (count($parameters->activeTrail) >= $level) {
          // Active trail array is child-first. Reverse it, and pull the new menu
          // root based on the parent of the configured start level.
          $menu_trail_ids = array_reverse(array_values($parameters->activeTrail));
          $menu_root = $menu_trail_ids[$level - 1];
          $parameters->setRoot($menu_root)->setMinDepth(1);
          if ($depth > 0) {
            $parameters->setMaxDepth(min($level - 1 + $depth - 1, $menu_tree->maxDepth()));
          }
        }
        else {
          return [];
        }
      }

      $tree = $menu_tree->load($menu, $parameters);

      // Build the menu tree taking into account access and sorting.
      $tree = $menu_tree->transform($tree, $manipulators);
      $tree = $menu_tree->build($tree);

      // Add menu level classes to the tree.
      branchee_block_add_menu_class($tree, 1);

      $theme = $config['theme'] == 'other' ? $config['theme_other'] : $config['theme'];

      // Add a drupal alter in order to alter the menu if needed.
      \Drupal::moduleHandler()->alter('branchee_block_menu_data', $tree, $this);

      // Construct the Branchee_block render array.
      $form = [
        '#theme' => 'branchee_menu_block',
        '#branchee_theme' => $theme,
        '#menu' => $tree,
        '#attached' => [
          'library' => [
            'branchee_block/branchee-menu',
          ],
        ],
        '#cache' => [
          'contexts' => [
            'url.path',
          ],
        ],
      ];

      return $form;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $defaults = $this->defaultConfiguration();

    $config = $this->getConfiguration();

    $entity_manager = \Drupal::entityTypeManager();
    $menus = $entity_manager->getStorage('menu')->loadMultiple();

    $menu_options = [];
    foreach ($menus as $menu) {
      $menu_options[$menu->get('id')] = $menu->get('label');
    }

    $form['branchee_menu_block_menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a Menu'),
      '#description' => $this->t('Select a Menu to render with Branchee'),
      '#default_value' => isset($config['menu']) ? $config['menu'] : $defaults['menu'],
      '#options' => $menu_options,
      '#required' => TRUE,
    ];

    $form['menu_levels'] = [
      '#type' => 'details',
      '#title' => $this->t('Menu levels'),
      // Open if not set to defaults.
      '#open' => $defaults['level'] !== $config['level'] || $defaults['depth'] !== $config['depth'],
      '#process' => [[get_class(), 'processMenuLevelParents']],
    ];

    $options = range(0, 9);
    unset($options[0]);

    $form['menu_levels']['level'] = [
      '#type' => 'select',
      '#title' => $this->t('Initial visibility level'),
      '#default_value' => isset($config['level']) ? $config['level'] : $defaults['level'],
      '#options' => $options,
      '#description' => $this->t('The menu is only visible if the menu item for the current page is at this level or below it. Use level 1 to always display this menu.'),
      '#required' => TRUE,
    ];

    $options[0] = $this->t('Unlimited');
    $form['menu_levels']['depth'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of levels to display'),
      '#default_value' => isset($config['depth']) ? $config['depth'] : $defaults['depth'],
      '#options' => $options,
      '#description' => $this->t('This maximum number includes the initial level.'),
      '#required' => TRUE,
    ];

    $theme_options = [
      'base' => 'Base',
      'minimal' => 'Minimal',
      'rainbow' => 'Rainbow',
      'dark-rainbow' => 'Dark Rainbow',
      'deep-blue' => 'Deep Blue',
      'other' => 'Other',
    ];

    $form['branchee_menu_block_theme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select a Theme'),
      '#description' => $this->t('Select a Theme for the branchee menu, or a custom class to apply to it.'),
      '#default_value' => isset($config['theme']) ? $config['theme'] : $defaults['theme'],
      '#options' => $theme_options,
      '#required' => TRUE,
    ];

    $form['branchee_menu_block_theme_other'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other'),
      '#description' => $this->t('Type in a custom theme name to use.'),
      '#default_value' => isset($config['theme_other']) ? $config['theme_other'] : $defaults['theme_other'],
      '#states' => array(
        'visible' => array(
          ':input[name="settings[branchee_menu_block_theme]"]' => array('value' => 'other'),
        ),
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('menu', $form_state->getValue('branchee_menu_block_menu'));
    $this->setConfigurationValue('level', $form_state->getValue('level'));
    $this->setConfigurationValue('depth', $form_state->getValue('depth'));
    $this->setConfigurationValue('theme', $form_state->getValue('branchee_menu_block_theme'));
    $this->setConfigurationValue('theme_other', $form_state->getValue('branchee_menu_block_theme_other'));
  }

  /**
   * Form API callback: Processes the menu_levels field element.
   *
   * Adjusts the #parents of menu_levels to save its children at the top level.
   */
  public static function processMenuLevelParents(&$element, FormStateInterface $form_state, &$complete_form) {
    array_pop($element['#parents']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'menu' => 'main',
      'level' => 1,
      'depth' => 0,
      'theme' => 'base',
      'theme_other' => '',
    ];
  }
}

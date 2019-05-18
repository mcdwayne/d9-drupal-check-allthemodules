<?php

namespace Drupal\dynamic_menu\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;
use Drupal\system\Plugin\Block\SystemMenuBlock;

/**
 * Provides an extended Menu block.
 *
 * @Block(
 *   id = "dynamic_menu",
 *   admin_label = @Translation("Dynamic menu block"),
 *   category = @Translation("Dynamic menu"),
 *   deriver = "Drupal\dynamic_menu\Plugin\Derivative\DynamicMenu"
 * )
 */
class DynamicMenu extends SystemMenuBlock
{

    /**
     * {@inheritdoc}
     * This shows UI settings page where user can allocate this block
     * to menu with customized settings.
     */

    public function blockForm($form, FormStateInterface $form_state)
    {
        $config = $this->configuration;
        $defaults = $this->defaultConfiguration();

        $form['menu_levels'] = [
            '#type' => 'details',
            '#title' => $this->t('Menu levels'),
            // Open if not set to defaults.
            '#open' => $defaults['level'] !== $config['level'] || $defaults['depth'] !== $config['depth'],
            '#process' => [[get_class(), 'processMenuLevelParents']],
        ];

        $options = range(0, $this->menuTree->maxDepth());
        unset($options[0]);

        $options[0] = $this->t('Unlimited');

        $form['menu_levels']['depth'] = [
            '#type' => 'select',
            '#title' => $this->t('Number of levels to display'),
            '#default_value' => $config['depth'],
            '#options' => $options,
            '#description' => $this->t('This maximum number includes the initial level.'),
            '#required' => true,
        ];

        $form['advanced'] = [
            '#type' => 'details',
            '#title' => $this->t('Advanced options'),
            '#open' => false,
            '#process' => [[get_class(), 'processMenuBlockFieldSets']],
        ];

        $form['advanced']['expand'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('<strong>Expand all menu links</strong>'),
            '#default_value' => $config['expand'],
            '#description' => $this->t('All menu links that have children will "Show as expanded".'),
        ];

        $form['style'] = [
            '#type' => 'details',
            '#title' => $this->t('HTML and style options'),
            '#open' => false,
            '#process' => [[get_class(), 'processMenuBlockFieldSets']],
        ];

        $form['style']['suggestion'] = [
            '#type' => 'machine_name',
            '#title' => $this->t('Theme hook suggestion'),
            '#default_value' => $config['suggestion'],
            '#field_prefix' => '<code>menu__</code>',
            '#description' => $this->t('A theme hook suggestion can be used to override the default HTML and CSS classes for menus found in <code>menu.html.twig</code>.'),
            '#machine_name' => [
                'error' => $this->t('The theme hook suggestion must contain only lowercase letters, numbers, and underscores.'),
            ],
        ];

        // Open the details field sets if their config is not set to defaults.
        foreach (['menu_levels', 'advanced', 'style'] as $fieldSet) {
            foreach (array_keys($form[$fieldSet]) as $field) {
                if (isset($defaults[$field]) && $defaults[$field] !== $config[$field]) {
                    $form[$fieldSet]['#open'] = true;
                }
            }
        }

        return $form;
    }

    /**
     * Form API callback: Processes the elements in field sets.
     *
     * Adjusts the #parents of field sets to save its children at the top level.
     */
    public static function processMenuBlockFieldSets(&$element, FormStateInterface $form_state, &$complete_form)
    {
        array_pop($element['#parents']);
        return $element;
    }

    /**
     * {@inheritdoc}
     * Save settings into configuration array.
     * We are setting initial level to always 2 and expand menu links to 1(True)
     */

    public function blockSubmit($form, FormStateInterface $form_state)
    {
        $this->configuration['level'] = 2;
        $this->configuration['depth'] = $form_state->getValue('depth');
        $this->configuration['expand'] = 1;
        $this->configuration['suggestion'] = $form_state->getValue('suggestion');
    }

    /**
     * {@inheritdoc}
     * This function gets called on page load and
     * decides to show the sub menu block.
     * If menu don't have the sub menu link then the sub menu
     * block is not shown. Also only sub menu links are shown
     * for particular to menu and not others.
     */

    public function build()
    {
        //Get the menu
        $menu_name = $this->getDerivativeId();
        $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
        $parameters->setTopLevelOnly();
        $main_menu_top_level = $this->menuTree->load($menu_name, $parameters);

        // Adjust the menu tree parameters based on the block's configuration.
        $level = 1;
        $depth = $this->configuration['depth'];
        $expand = 1;

        $suggestion = $this->configuration['suggestion'];

        $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
        $parameters->setMinDepth($level);
        // When the depth is configured to zero, there is no depth limit. When depth
        // is non-zero, it indicates the number of levels that must be displayed.
        // Hence this is a relative depth that we must convert to an actual
        // (absolute) depth, that may never exceed the maximum depth.
        if ($depth > 0) {
            $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));
        }
        // If expandedParents is empty, the whole menu tree is built.
        if ($expand) {
            $parameters->expandedParents = array();
        }
        $menuLinkID = '';
        //Traverse and get current page active menu item
        foreach ($main_menu_top_level as $key => $value) {
            if ($value->inActiveTrail) {
                $menuLinkID = $key;
            }
        }

        // When a fixed parent item is set, root the menu tree at the given ID.
        if ($menuLinkID != '') {
            $parameters->setRoot($menuLinkID);

            // If the starting level is 1, we always want the child links to appear,
            // but the requested tree may be empty if the tree does not contain the
            // active trail.

            // Check if the tree contains links.
            $tree = $this->menuTree->load($menu_name, $parameters);

            if (empty($tree)) {
                // Change the request to expand all children and limit the depth to
                // the immediate children of the root.
                $parameters->expandedParents = array();
                $parameters->setMinDepth(1);
                $parameters->setMaxDepth(1);
                // Re-load the tree.
                $tree = $this->menuTree->load(null, $parameters);
            }

        }

        // Load the tree if we haven't already.
        if (!isset($tree)) {
            $tree = $this->menuTree->load($menu_name, $parameters);
        }
        // die(!empty($tree));
        $manipulators = array(
            array('callable' => 'menu.default_tree_manipulators:checkAccess'),
            array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
        );
        $tree = $this->menuTree->transform($tree, $manipulators);
        $build = $this->menuTree->build($tree);

        if (!empty($build['#theme'])) {
            // Add the configuration for use in menu_block_theme_suggestions_menu().
            $build['#menu_block_configuration'] = $this->configuration;
            // Remove the menu name-based suggestion so we can control its precedence
            // better in menu_block_theme_suggestions_menu().
            $build['#theme'] = 'menu';
        }

        return $build;
    }

    /**
     * {@inheritdoc}
     * This is default configuration values.
     * When form is rendered, these values are considered.
     * This is overriden function from SystemMenuBlock class
     */

    public function defaultConfiguration()
    {
        return [
            'level' => 2,
            'depth' => 1,
            'expand' => 1,
            'suggestion' => strtr($this->getDerivativeId(), '-', '_'),
        ];
    }

}

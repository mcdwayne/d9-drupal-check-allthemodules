<?php

namespace Drupal\bigmenu;

use Drupal\Core\Url;
use Drupal\menu_ui\MenuForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Element;

/**
 * Defines class for BigMenuForm.
 */
class BigMenuForm extends MenuForm {

  /**
   * The menu tree.
   *
   * @var array
   */
  protected $tree = [];

  /**
   * Overrides Drupal\menu_ui\MenuForm::buildOverviewForm() to limit the depth.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The form.
   */
  protected function buildOverviewForm(array &$form, FormStateInterface $form_state) {
    $menu_link = $this->getRequest()->query->get('menu_link');
    $form['#cache']['contexts'][] = 'url.query_args:menu_link';
    return $this->buildOverviewFormWithDepth($form, $form_state, 1, $menu_link);
  }

  /**
   * Build a shallow version of the overview form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param int $depth
   *   The depth.
   * @param string $menu_link
   *   (Optional) The starting menu link id.
   *
   * @return array
   *   The form.
   */
  protected function buildOverviewFormWithDepth(array &$form, FormStateInterface $form_state, $depth = 1, $menu_link = NULL) {
    // Ensure that menu_overview_form_submit() knows the parents of this form
    // section.
    if (!$form_state->has('menu_overview_form_parents')) {
      $form_state->set('menu_overview_form_parents', []);
    }

    // Use Menu UI adminforms.
    $form['#attached']['library'][] = 'menu_ui/drupal.menu_ui.adminforms';

    // Add a link to go back to the full menu.
    if ($menu_link) {
      $form['back_link'] = $this->entity->toLink($this->t('Back to @label top level', [
        '@label' => $this->entity->label(),
      ]), 'edit-form')->toRenderable();
    }

    $form['links'] = [
      '#type' => 'table',
      '#theme' => 'table__menu_overview',
      '#header' => [
        $this->t('Menu link'),
        $this->t('Edit children'),
        [
          'data' => $this->t('Enabled'),
          'class' => ['checkbox'],
        ],
        $this->t('Weight'),
        [
          'data' => $this->t('Operations'),
          'colspan' => 3,
        ],
      ],
      '#attributes' => [
        'id' => 'menu-overview',
      ],
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'menu-parent',
          'subgroup' => 'menu-parent',
          'source' => 'menu-id',
          'hidden' => TRUE,
          'limit' => $this->menuTree->maxDepth() - 1,
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'menu-weight',
        ],
      ],
    ];

    // No Links available (Empty menu)
    $form['links']['#empty'] = $this->t('There are no menu links yet. <a href=":url">Add link</a>.', [
      ':url' => $this->entity->toUrl('add-link-form', [
        'query' => ['destination' => $this->entity->toUrl('edit-form')->toString()],
      ])->toString(),
    ]);

    // Get the menu tree if it's not in our property.
    if (empty($this->tree)) {
      $this->tree = $this->getTree($depth, $menu_link);
    }

    // Determine the delta; the number of weights to be made available.
    $count = function (array $tree) {
      $sum = function ($carry, MenuLinkTreeElement $item) {
        return $carry + $item->count();
      };
      return array_reduce($tree, $sum);
    };

    // Tree maximum or 50.
    $delta = max($count($this->tree), 50);

    $links = $this->buildOverviewTreeForm($this->tree, $delta);

    $this->processLinks($form, $links, $menu_link);

    return $form;
  }

  /**
   * Format the links appropriately so draggable views will work.
   *
   * @param array $form
   *   The form array.
   * @param array $links
   *   An array of links.
   * @param string $menu_link
   *   A menu link plugin id.
   */
  public function processLinks(array &$form, array &$links, $menu_link) {
    foreach (Element::children($links) as $id) {
      if (isset($links[$id]['#item'])) {
        $element = $links[$id];

        $form['links'][$id]['#item'] = $element['#item'];

        // TableDrag: Mark the table row as draggable.
        $form['links'][$id]['#attributes'] = $element['#attributes'];
        $form['links'][$id]['#attributes']['class'][] = 'draggable';

        // TableDrag: Sort the table row according to its existing/configured
        // weight.
        $form['links'][$id]['#weight'] = $element['#item']->link->getWeight();

        // Add special classes to be used for tabledrag.js.
        $element['parent']['#attributes']['class'] = ['menu-parent'];
        $element['weight']['#attributes']['class'] = ['menu-weight'];
        $element['id']['#attributes']['class'] = ['menu-id'];

        $form['links'][$id]['title'] = [
          [
            '#theme' => 'indentation',
            '#size' => $element['#item']->depth - 1,
          ],
          $element['title'],
        ];

        $form['links'][$id]['root'][] = [];

        if ($form['links'][$id]['#item']->hasChildren) {
          if (is_null($menu_link) || (isset($menu_link) && $menu_link != $element['#item']->link->getPluginId())) {
            $uri = $this->entity->toUrl('edit-form', [
              'query' => ['menu_link' => $element['#item']->link->getPluginId()],
            ]);

            $form['links'][$id]['root'][] = [
              '#type' => 'link',
              '#title' => $this->t('Edit child items'),
              '#url' => $uri,
            ];
          }
        }

        $form['links'][$id]['enabled'] = $element['enabled'];
        $form['links'][$id]['enabled']['#wrapper_attributes']['class'] = ['checkbox', 'menu-enabled'];

        $form['links'][$id]['weight'] = $element['weight'];

        // Operations (dropbutton) column.
        $form['links'][$id]['operations'] = $element['operations'];

        $form['links'][$id]['id'] = $element['id'];
        $form['links'][$id]['parent'] = $element['parent'];
      }
    }
  }

  /**
   * Gets the menu tree.
   *
   * @param int $depth
   *   The depth.
   * @param string $root
   *   An optional root menu link plugin id.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   An array of menu link tree elements.
   */
  protected function getTree($depth, $root = NULL) {
    $tree_params = new MenuTreeParameters();
    $tree_params->setMaxDepth($depth);

    if ($root) {
      $tree_params->setRoot($root);
    }

    $tree = $this->menuTree->load($this->entity->id(), $tree_params);

    // We indicate that a menu administrator is running the menu access check.
    $this->getRequest()->attributes->set('_menu_admin', TRUE);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    $this->getRequest()->attributes->set('_menu_admin', FALSE);

    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirectUrl(Url::fromUserInput($this->getRedirectDestination()->get()));
  }

}

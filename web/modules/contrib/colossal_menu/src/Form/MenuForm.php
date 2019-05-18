<?php

namespace Drupal\colossal_menu\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeParameters;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MenuForm.
 *
 * @package Drupal\colossal_menu\Form
 */
class MenuForm extends EntityForm {

  /**
   * Entity Manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Menu Tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * Constructor.
   */
  public function __construct(EntityManagerInterface $entity_manager, MenuLinkTreeInterface $menu_link_tree) {
    $this->entityManager = $entity_manager;
    $this->menuLinkTree = $menu_link_tree;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('colossal_menu.link_tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $menu = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $menu->label(),
      '#description' => $this->t("Label for the Menu."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $menu->id(),
      '#machine_name' => [
        'exists' => '\Drupal\colossal_menu\Entity\Menu::load',
      ],
      '#disabled' => !$menu->isNew(),
    ];

    // Add menu links administration form for existing menus.
    if (!$menu->isNew()) {
      // Form API supports constructing and validating self-contained sections
      // within forms, but does not allow handling the form section's submission
      // equally separated yet. Therefore, we use a $form_state key to point to
      // the parents of the form section.
      // @see self::submitOverviewForm()
      $form_state->set('links', ['links']);
      $form['links'] = [];
      $form['links'] = $this->buildOverviewForm($form['links'], $form_state);
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $menu = $this->entity;

    if (!$menu->isNew()) {
      $this->submitOverviewForm($form, $form_state);
    }

    $status = $menu->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Menu.', [
          '%label' => $menu->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Menu.', [
          '%label' => $menu->label(),
        ]));
    }
    $form_state->setRedirectUrl($menu->urlInfo('collection'));
  }

  /**
   * Submit handler for the menu overview form.
   *
   * This function takes great care in saving parent items first, then items
   * underneath them. Saving items in the incorrect order can break the tree.
   */
  protected function submitOverviewForm(array $complete_form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();

    foreach ($input['links'] as $id => $input) {
      $storage = $this->entityManager->getStorage('colossal_menu_link');
      $link = $storage->load($id);

      $diff = FALSE;

      if (array_key_exists('parent', $input) && $link->get('parent')->access('edit')) {
        if (!$link->getParent() && $input['parent']) {
          $diff = TRUE;
          $link->setParent($input['parent']);
        }
        elseif ($link->getParent() && $link->getParent()->id() != $input['parent']) {
          $diff = TRUE;
          $link->setParent($input['parent']);
        }
      }

      if (array_key_exists('weight', $input) && $link->get('weight')->access('edit')) {
        if ($link->getWeight() != $input['weight']) {
          $diff = TRUE;
          $link->setWeight($input['weight']);
        }
      }

      if (array_key_exists('enabled', $input) && $link->get('enabled')->access('edit')) {
        $enabled = (bool) $input['enabled'];
        if ($link->isEnabled() != $enabled) {
          $diff = TRUE;
          $link->setEnabled($enabled);
        }
      }

      if ($diff) {
        $link->save();
      }
    }
  }

  /**
   * Form constructor to edit an entire menu tree at once.
   *
   * Shows for one menu the menu links accessible to the current user and
   * relevant operations.
   *
   * This form constructor can be integrated as a section into another form. It
   * relies on the following keys in $form_state:
   * - menu: A menu entity.
   * - menu_overview_form_parents: An array containing the parent keys to this
   *   form.
   * Forms integrating this section should call menu_overview_form_submit() from
   * their form submit handler.
   */
  protected function buildOverviewForm(array &$form, FormStateInterface $form_state) {
    $menu = $this->entity;

    // Ensure that menu_overview_form_submit() knows the parents of this form
    // section.
    if (!$form_state->has('menu_overview_form_parents')) {
      $form_state->set('menu_overview_form_parents', []);
    }

    $form['links'] = [
      '#type' => 'table',
      '#sorted' => TRUE,
      '#header' => [
        $this->t('Title'),
        $this->t('Enabled'),
        $this->t('Weight'),
        [
          'data' => $this->t('Operations'),
          'colspan' => 3,
        ],
      ],
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'link-parent',
          'subgroup' => 'link-parent',
          'source' => 'link-id',
          'hidden' => TRUE,
          'limit' => $this->menuLinkTree->maxDepth(),
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'link-weight',
        ],
      ],
    ];

    $params = new MenuTreeParameters();
    $tree = $this->menuLinkTree->load($menu->id(), $params);

    $elements = [];
    foreach ($tree as $item) {
      $this->buildLinkElement($elements, $item);
    }

    $form['links'] = $form['links'] + $elements;

    return $form;
  }

  /**
   * Build an array of link elements.
   *
   * @param array $elements
   *   An array of form elements to be filled.
   * @param \Drupal\Core\Menu\MenuLinkTreeElement $item
   *   Menu Link Tree element.
   * @param int $depth
   *   The current depth.
   */
  protected function buildLinkElement(&$elements, MenuLinkTreeElement $item, $depth = 0) {
    $id = $item->link->id();
    $link = $item->link;
    $elements[$id] = [
      '#weight' => $link->getWeight(),
    ];

    if ($link->get('parent')->access('edit') && $link->get('weight')->access('edit')) {
      $elements[$id]['#attributes']['class'][] = 'draggable';
    }

    $text = [];
    if (!$link->isExternal() && $link->getRouteName() == '<none>') {
      $text = [
        '#plain_text' => $link->getTitle(),
      ];
    }
    else {
      $text = Link::fromTextAndUrl($link->getTitle(), $link->getUrlObject())->toRenderable();
    }

    $elements[$id]['indent'] = [
      [
        '#theme' => 'indentation',
        '#size' => $depth,
      ],
      [
        $text,
      ],
    ];

    if ($link->get('enabled')->access('edit')) {
      $elements[$id]['enabled'] = [
        '#type' => 'checkbox',
        '#default_value' => $link->isEnabled(),
        '#title' => $this->t('Enabled'),
        '#title_display' => 'invisible',
      ];
    }
    else {
      $elements[$id]['enabled'] = [
        '#markup' => $link->isEnabled() ? $this->t('Enabled') : $this->t('Disabled'),
      ];
    }

    $elements[$id]['weight'] = [
      '#type' => 'weight',
      '#delta' => 50,
      '#default_value' => $link->getWeight(),
      '#title' => $this->t('Weight for @title', ['@title' => $link->getTitle()]),
      '#title_display' => 'invisible',
      '#attributes' => [
        'class' => [
          'link-weight',
        ],
      ],
      '#access' => $link->get('weight')->access('edit'),
    ];

    $operations = [];
    if ($link->access('update')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => $link->getEditRoute(),
        'query' => $this->getDestinationArray(),
      ];
    }
    if ($link->access('delete')) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => $link->getDeleteRoute(),
        'query' => $this->getDestinationArray(),
      ];
    }

    $elements[$id]['operations'] = [
      '#type' => 'operations',
      '#links' => $operations,
    ];

    $elements[$id]['id'] = [
      '#type' => 'hidden',
      '#default_value' => $id,
      '#attributes' => [
        'class' => [
          'link-id',
        ],
      ],
    ];

    $elements[$id]['parent'] = [
      '#type' => 'hidden',
      '#default_value' => ($link->getParent()) ? $link->getParent()->id() : 0,
      '#attributes' => [
        'class' => [
          'link-parent',
        ],
      ],
      '#access' => $link->get('parent')->access('edit'),
    ];

    if (!empty($item->subtree)) {
      $subdepth = $depth + 1;
      foreach ($item->subtree as $subitem) {
        $this->buildLinkElement($elements, $subitem, $subdepth);
      }
    }
  }

}

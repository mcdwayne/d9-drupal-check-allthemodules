<?php

namespace Drupal\menumatic\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Routing\RouteBuilderInterface;

/**
 * Configure menumatic expanded generation form.
 */
class GenerateExpandedForm extends FormBase implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The menu tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The menu storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuStorage;

  /**
   * The route builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Constructs a MenuDevelGenerate object.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu tree service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $menu_storage
   *   The menu storage.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder service.
   */
  public function __construct(MenuLinkTreeInterface $menu_tree, EntityStorageInterface $menu_storage, RouteBuilderInterface $route_builder) {
    $this->menuLinkTree = $menu_tree;
    $this->menuStorage = $menu_storage;
    $this->routeBuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $container->get('menu.link_tree'),
      $entity_manager->getStorage('menu'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menumatic_generate_expanded';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Load menus.
    $type_menus = $this->menuStorage->loadMultiple();
    $type_menus_options = [];
    foreach ($type_menus as $mid => $menu) {
      $type_menus_options[$mid] = $menu->get('label');
    }

    $form['menu_name'] = [
      '#title' => $this->t('Choose menu'),
      '#description' => $this->t('The maximum depth for a link and all its children is fixed. Some menu links may not be available as parents if selecting them would exceed this limit.'),
      '#type' => 'select',
      '#options' => $type_menus_options,
      '#required' => TRUE,
    ];

    $form['expanded_value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expanded value'),
      '#default_value' => TRUE,
    ];

    $form['expanded_affect_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Affect disabled menu items'),
      '#default_value' => TRUE,
    ];

    $form['min_depth'] = [
      '#type' => 'select',
      '#title' => $this->t('Minimum depth'),
      '#options' => range(0, 10),
      '#default_value' => 0,
    ];

    $form['max_depth'] = [
      '#type' => 'select',
      '#title' => $this->t('Maximum depth'),
      '#options' => range(0, 10),
      '#default_value' => 1,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Callback to process menu.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   * @param bool $expanded_value
   *   Value of the expanded field.
   * @param bool $expanded_affect_disabled
   *   Flag to modify disabled menu.
   */
  protected function processMenuLinkTreeElement(array $tree, $expanded_value, $expanded_affect_disabled) {

    // Process subtree.
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement $menu_item */
    foreach ($tree as $menu_item) {

      // \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent; $menu_link.
      $menu_link = $menu_item->link;
      if ($menu_item->subtree) {
        $this->processMenuLinkTreeElement($menu_item->subtree, $expanded_value, $expanded_affect_disabled);
      }

      // Process current menu item.
      if ($menu_link->isExpanded() === $expanded_value) {
        continue;
      }
      else {
        if (!$menu_link->isEnabled() && !$expanded_affect_disabled) {
          continue;
        }

        $definitions = $menu_link->getPluginDefinition();
        $definitions['expanded'] = $expanded_value;
        $menu_link->updateLink($definitions, TRUE);

      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $menu_name = $values['menu_name'];
    $min_depth = (int) $values['min_depth'];
    $max_depth = (int) $values['max_depth'];
    $expanded_value = (int) $values['expanded_value'];
    $expanded_affect_disabled = (int) $values['expanded_affect_disabled'];

    $parameters = new MenuTreeParameters();
    $parameters->setMinDepth($min_depth);
    $parameters->setMaxDepth($max_depth);
    $tree = $this->menuLinkTree->load($menu_name, $parameters);
    $this->processMenuLinkTreeElement($tree, $expanded_value === 1, $expanded_affect_disabled === 1);

    $this->routeBuilder->rebuildIfNeeded();
    drupal_flush_all_caches();

  }

}

<?php

namespace Drupal\entity_submenu_block\Plugin\Block;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Menu\InaccessibleMenuLink;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\system\Plugin\Block\SystemMenuBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an Entity Submenu Block.
 *
 * @Block(
 *   id = "entity_submenu_block",
 *   admin_label = @Translation("Entity Submenu Block"),
 *   category = @Translation("Menus"),
 *   deriver = "Drupal\entity_submenu_block\Plugin\Derivative\EntitySubmenuBlock"
 * )
 */
class EntitySubmenuBlock extends SystemMenuBlock {

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * All entity types.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $entityTypes;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $static = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $static->setMenuActiveTrail($container->get('menu.active_trail'));
    $static->setEntityTypeManager($container->get('entity_type.manager'));
    $static->setEntityDisplayRepository($container->get('entity_display.repository'));
    $static->setEntityTypes($container->get('entity_type.manager')->getDefinitions());
    return $static;
  }

  public function setMenuActiveTrail(MenuActiveTrailInterface $menuActiveTrail) {
    $this->menuActiveTrail = $menuActiveTrail;
  }

  public function setEntityTypeManager(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public function setEntityDisplayRepository(EntityDisplayRepositoryInterface $entityDisplayRepository) {
    $this->entityDisplayRepository = $entityDisplayRepository;
  }

  public function setEntityTypes(array $entityTypes) {
    $this->entityTypes = $entityTypes;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // We don't need the menu levels options from the parent.
    unset($form['menu_levels']);

    $config = $this->getConfiguration();

    // Display non-entities.
    $form['display_non_entities'] = [
      '#title' => $this->t('Display non-entities'),
      '#type' => 'checkbox',
      '#default_value' => $this->getConfigurationValue($config, 'display_non_entities'),
    ];

    // Only display entities with content in current language.
    $form['only_current_language'] = [
      '#type' => 'checkbox',
      '#title' => t('Only display entities with content in current language'),
      '#default_value' => $this->getConfigurationValue($config, 'only_current_language'),
    ];

    // View modes fieldgroup.
    $form['view_modes'] = [
      '#title' => $this->t('View modes'),
      '#description' => $this->t('View modes to be used when submenu items are displayed as content entities'),
      '#type' => 'fieldgroup',
      '#process' => [[get_class(), 'processFieldSets']],
    ];

    // A select list of view modes for each entity type.
    foreach ($this->getEntityTypes() as $entity_type) {
      $field = 'view_mode_' . $entity_type;
      $view_modes = $this->entityDisplayRepository->getViewModeOptions($entity_type);
      $form['view_modes'][$field] = [
        '#title' => $this->entityTypeManager->getDefinition($entity_type)->getLabel(),
        '#type' => 'select',
        '#options' => $view_modes,
        '#default_value' => $this->getConfigurationValue($config, $field, array_keys($view_modes)),
      ];
    }

    return $form;
  }

  /**
   * Form API callback: Processes the elements in field sets.
   *
   * Adjusts the #parents of field sets to save its children at the top level.
   */
  public static function processFieldSets(&$element, FormStateInterface $form_state, &$complete_form) {
    array_pop($element['#parents']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach (['display_non_entities', 'only_current_language'] as $field) {
      $this->setConfigurationValue($field, $form_state->getValue($field));
    }
    foreach ($this->getEntityTypes() as $entity_type) {
      $field = 'view_mode_' . $entity_type;
      $value = $form_state->getValue($field);
      $this->setConfigurationValue($field, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#theme' => 'entity_submenu',
      '#menu_name' => NULL,
      '#menu_items' => [],
    ];

    // Get the menu name.
    $menu_name = $this->getDerivativeId();

    // Return empty menu items array if the active trail is not in this menu.
    if (empty($this->menuActiveTrail->getActiveLink($menu_name))) {
      return $build;
    }

    // The menu name is only set if the active trail is in this menu.
    $build['#menu_name'] = $menu_name;

    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);

    // Get current level from end of active trail.
    $level = count($parameters->activeTrail);
    $parameters->setMinDepth($level);

    // We only want the current level.
    $parameters->setMaxDepth($level);

    // We only want enabled links.
    $parameters->onlyEnabledLinks();

    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);

    $config = $this->getConfiguration();
    $language = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    foreach ($tree as $element) {
      // Skip inaccessible links.
      if ($element->link instanceof InaccessibleMenuLink) {
        continue;
      }
      $url = $element->link->getUrlObject();
      // Only try to get route parameters from routed links.
      if ($url->isRouted()) {
        $routeParams = $url->getRouteParameters();
        reset($routeParams);
        $entity_type = key($routeParams);
        if (in_array($entity_type, $this->getEntityTypes())) {
          // The link is an entity link.
          $entity = $this->entityTypeManager->getStorage($entity_type)->load($routeParams[$entity_type]);
          if ($this->getConfigurationValue($config, 'only_current_language') == 1) {
            $languages = $entity->getTranslationLanguages();
            if (!array_key_exists($language, $languages)) {
              // Skip this entity as content is not translated in current language.
              continue;
            }
          }
          // Get render array and continue to next menu item.
          $build['#menu_items'][] = $this->entityTypeManager->getViewBuilder($entity_type)->view($entity, $config['view_mode_' . $entity_type]);
          continue;
        }
      }
      // The link is a routed non-entity link or an external link.
      // If the configuration option is set, create a render array.
      if ($this->getConfigurationValue($config, 'display_non_entities') == 1) {
        $build['#menu_items'][] = [
          '#theme' => 'entity_submenu_item',
          '#url' => $url,
          '#title' => $element->link->getTitle(),
        ];
      }
    }

    return $build;
  }

  /**
   * Returns a configuration value for a specified field.
   *
   * @param array $config
   *   Array containing the configuration.
   * @param string $field
   *   Name of the field to get a value for.
   * @param array $valid
   *   Optional array containing valid values for the field.
   *
   * @return value
   *   Value for the field or NULL.
   */
  protected function getConfigurationValue(array $config, $field, array $valid = NULL) {
    $value = NULL;
    if (isset($config[$field]) && !empty($config[$field])) {
      if (is_array($valid)) {
        if (in_array($config[$field], $valid)) {
          $value = $config[$field];
        }
      }
      else {
        $value = $config[$field];
      }
    }

    return $value;
  }

  /**
   * Returns a list of valid entity types.
   *
   * @return array
   *   Valid entity type names.
   */
  protected function getEntityTypes() {
    $entity_types = ['node'];
    foreach ($this->entityTypes as $entity_type => $definition) {
      if ($entity_type != 'node' && $this->isValidEntity($entity_type)) {
        $entity_types[] = $entity_type;
      }
    }

    return $entity_types;
  }

  /**
   * Filters entities based on their view builder handlers.
   *
   * @param string $entity_type
   *   The entity type of the entity that needs to be validated.
   *
   * @return bool
   *   TRUE if the entity has the correct view builder handler, FALSE if the
   *   entity doesn't have the correct view builder handler.
   */
  protected function isValidEntity($entity_type) {
    return $this->entityTypes[$entity_type]->get('field_ui_base_route') && $this->entityTypes[$entity_type]->hasViewBuilderClass();
  }

}

<?php

namespace Drupal\menu_link\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\menu_link\Plugin\Menu\Form\MenuLinkFieldForm;
use Drupal\menu_link\Plugin\Menu\MenuLinkField;
use Drupal\system\Entity\Menu;

/**
 * Defines a menu link field type which stores the link, parent and menu.
 *
 * @FieldType(
 *   id = "menu_link",
 *   label = @Translation("Menu link"),
 *   description = @Translation("Stores a title, menu and parent to insert a link to the current entity."),
 *   default_widget = "menu_link_default",
 *   list_class = "\Drupal\menu_link\Plugin\Field\MenuLinkItemList",
 *   default_formatter = "menu_link",
 *   column_groups = {
 *     "title" = {
 *       "label" = @Translation("Title"),
 *       "translatable" = TRUE
 *     },
 *     "description" = {
 *       "label" = @Translation("Description"),
 *       "translatable" = TRUE
 *     },
 *     "menu_name" = {
 *       "label" = @Translation("Menu name"),
 *       "translatable" = TRUE
 *     },
 *     "parent" = {
 *       "label" = @Translation("Parent"),
 *       "translatable" = TRUE
 *     },
 *   },
 * )
 */
class MenuLinkItem extends FieldItemBase {

  /**
   * The menu plugin manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuPluginManager;

  /**
   * The menu parent form selector.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuParentFormSelector;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    $this->menuPluginManager = \Drupal::service('plugin.manager.menu.link');
    $this->menuParentFormSelector = \Drupal::service('menu.parent_form_selector');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
        'menu_link_per_translation' => FALSE,
      ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element['menu_link_per_translation'] = [
      '#type' => 'checkbox',
      '#title' => t('Expose a menu link per translation'),
      '#default_value' => $this->getSetting('menu_link_per_translation'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = parent::defaultFieldSettings();

    $settings['available_menus'] = ['main'];
    $settings['default_menu_parent'] = 'main:';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::fieldSettingsForm($form, $form_state);

    $menu_options = $this->getMenuNames();

    $form['available_menus'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Available menus'),
      '#default_value' => $this->getSetting('available_menus'),
      '#options' => $menu_options,
      '#description' => $this->t('The menus available to place links in for this kind of entity.'),
      '#required' => TRUE,
    ];

    $parent_options = [];
    // Make sure the setting is normalized to an associative array.
    $available_menus = array_filter($this->getSetting('available_menus'));
    $available_menus = array_combine($available_menus, $available_menus);
    foreach ($available_menus as $name) {
      if (isset($menu_options[$name])) {
        $parent_options["$name:"] = $menu_options[$name];
      }
    }
    $form['default_menu_parent'] = [
      '#type' => 'select',
      '#title' => $this->t('Default menu for new links'),
      '#default_value' => $this->getSetting('default_menu_parent'),
      '#options' => $parent_options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $definitions = [];

    $definitions['menu_name'] = DataDefinition::create('string')
      ->setLabel(t('Menu'));
    $definitions['title'] = DataDefinition::create('string')
      ->setLabel(t('Menu link title'));
    $definitions['description'] = DataDefinition::create('string')
      ->setLabel(t('Menu link description'));
    $definitions['parent'] = DataDefinition::create('string')
      ->setLabel(t('Menu link parent'))
      ->setSetting('default', '');
    $definitions['weight'] = DataDefinition::create('integer')
      ->setLabel(t('Menu link weight'));

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [];

    $schema['columns']['menu_name'] = [
      'description' => 'The menu of the link',
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ];

    $schema['columns']['title'] = [
      'description' => 'The menu link text.',
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ];

    $schema['columns']['description'] = [
      'description' => 'The description of the menu link.',
      'type' => 'blob',
      'size' => 'big',
      'not null' => FALSE,
    ];

    $schema['columns']['parent'] = [
      'description' => 'The parent of the menu link',
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ];

    $schema['columns']['weight'] = [
      'description' => 'The weight of the menu link',
      'type' => 'int',
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    $this->doSave();
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();

    $plugin_id = $this->getMenuPluginId();
    if ($this->menuPluginManager->hasDefinition($plugin_id)) {
      $this->menuPluginManager->removeDefinition($plugin_id, FALSE);
    }
  }

  /**
   * Saves the plugin definition.
   */
  protected function doSave() {
    $menu_link_per_translation = $this->getSetting('menu_link_per_translation');

    // We only update the menu link definition when working with the original
    // language value of the field, otherwise, we can never properly update or
    // remove the menu link.
    // @todo - use the FieldTranslationSynchronizer
    // https://www.drupal.org/node/2403455
    if (!$menu_link_per_translation && ($this->getLangcode() != $this->getEntity()->getUntranslated()->language()->getId())) {
      return;
    }
    $langcode = $menu_link_per_translation ? $this->getLangcode() : LanguageInterface::LANGCODE_NOT_SPECIFIED;
    $plugin_id = $this->getMenuPluginId($langcode);

    // When the entity is saved via a plugin instance, we should not call the
    // menu tree manager to update the definition a second time.
    if ($menu_plugin_definition = $this->getMenuPluginDefinition($langcode)) {
      if (!$this->menuPluginManager->hasDefinition($plugin_id)) {
        $this->menuPluginManager->addDefinition($plugin_id, $menu_plugin_definition);
      }
      else {
        $this->menuPluginManager->updateDefinition($plugin_id, $menu_plugin_definition, FALSE);
      }
    }
    else {
      $this->menuPluginManager->removeDefinition($plugin_id, FALSE);
    }
  }

  /**
   * Generates the plugin ID for the associated menu link.
   *
   * @param string $langcode
   *   (optional) The langcode to take into account.
   *
   * @return string
   *   The Plugin ID.
   */
  public function getMenuPluginId($langcode = NULL) {
    if ($langcode === NULL) {
      $menu_link_per_translation = $this->getSetting('menu_link_per_translation');
      $langcode = $menu_link_per_translation ? $this->getLangcode() : LanguageInterface::LANGCODE_NOT_SPECIFIED;
    }

    $field_name = $this->definition->getFieldDefinition()->getName();
    $entity_type_id = $this->getEntity()->getEntityTypeId();
    return 'menu_link_field:' . "{$entity_type_id}_{$field_name}_{$this->getEntity()->uuid()}_$langcode";
  }

  /**
   * Generates the plugin definition of the associated menu link.
   *
   * @return array|false
   *   The menu plugin definition, otherwise FALSE.
   */
  protected function getMenuPluginDefinition($langcode) {
    $menu_definition = [];

    // If there is no menu name selected, you don't have a valid menu plugin.
    if (!$this->values['menu_name']) {
      return FALSE;
    }
    
    $entity = $this->getEntity();
    $menu_definition['id'] = $this->getMenuPluginId($langcode);

    if ($entity instanceof TranslatableInterface && $entity->hasTranslation($langcode)) {
      $entity = $entity->getTranslation($langcode);
    }

    $menu_definition['title'] = $this->values['title'] ?: $entity->label();
    $menu_definition['description'] = isset($this->values['description']) ? $this->values['description'] : '';
    $menu_definition['title_max_length'] = $this->getFieldDefinition()->getSetting('max_length');
    $menu_definition['menu_name'] = $this->values['menu_name'];
    $menu_definition['parent'] = isset($this->values['parent']) ? $this->values['parent'] : '';
    $menu_definition['weight'] = isset($this->values['weight']) ? $this->values['weight'] : 0;
    $menu_definition['class'] = MenuLinkField::class;
    $menu_definition['form_class'] = MenuLinkFieldForm::class;
    $menu_definition['metadata']['entity_id'] = $entity->id();
    $menu_definition['metadata']['entity_type_id'] = $entity->getEntityTypeId();
    $menu_definition['metadata']['field_name'] = $this->definition->getFieldDefinition()->getName();
    $menu_definition['metadata']['langcode'] = $langcode;
    $menu_definition['metadata']['translatable'] = $entity->getEntityType()->isTranslatable();

    $url = $entity->toUrl('canonical');
    $menu_definition['route_name'] = $url->getRouteName();
    $menu_definition['route_parameters'] = $url->getRouteParameters();

    return $menu_definition;
  }

  /**
   * Returns available menu names.
   *
   * @return string[]
   *   Returns menu labels, keyed by menu ID.
   */
  protected function getMenuNames() {
    if ($custom_menus = Menu::loadMultiple()) {
      foreach ($custom_menus as $menu_name => $menu) {
        $custom_menus[$menu_name] = $menu->label();
      }
      asort($custom_menus);
    }

    return $custom_menus;
  }

}

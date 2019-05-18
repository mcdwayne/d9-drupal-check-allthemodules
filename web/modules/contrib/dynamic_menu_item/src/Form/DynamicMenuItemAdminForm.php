<?php

namespace Drupal\dynamic_menu_item\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Contains module administration webform.
 */
class DynamicMenuItemAdminForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * Holds the entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The parent form selector service.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuParentSelector;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new \Drupal\dynamic_menu_item\Form\DynamicMenuItemAdminForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Menu\MenuParentFormSelectorInterface $menuParentSelector
   *   The menu parent form selector service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MenuParentFormSelectorInterface $menuParentSelector, MessengerInterface $messenger, ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entityTypeManager;
    $this->menuParentSelector = $menuParentSelector;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('menu.parent_form_selector'),
      $container->get('messenger'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dynamic_menu_item.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dynamic_menu_item_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dynamic_menu_item.settings');

    /** @var \Drupal\Core\Menu\MenuParentFormSelectorInterface $menuParentSelector */
    $menu_names = menu_ui_get_menus();
    $parent_element = $this->menuParentSelector
      ->parentSelectElement($config->get('menu_item_parent'), '', $menu_names);

    // If no possible parent menu items were found, there is nothing to display.
    if (empty($parent_element)) {
      $this->messenger->addMessage($this->t('No possible parent menu items found.'), 'warning');
      return;
    }

    $form['menu_item_parent'] = $parent_element;
    $form['menu_item_parent']['#title'] = $this->t('Parent item');
    $form['menu_item_parent']['#attributes']['class'][] = 'menu-parent-select';

    $types = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();

    foreach ($types as $type) {
      $content_types[$type->id()] = $type->label();
    }

    $form['menu_item_title'] = [
      '#type' => 'textfield',
      '#title' => 'Menu item title',
      '#description' => $this->t('Title to be used for Menu Item.'),
      '#default_value' => $config->get('menu_item_title'),
    ];

    $form['menu_item_description'] = [
      '#type' => 'textfield',
      '#title' => 'Menu item description',
      '#description' => $this->t('Description to be used for Menu Item.'),
      '#default_value' => $config->get('menu_item_description'),
    ];

    $form['menu_item_node_id_link'] = [
      '#type' => 'textfield',
      '#title' => 'Node ID',
      '#description' => $this->t('Link to particular node'),
      '#default_value' => $config->get('menu_item_node_id_link'),
    ];

    $form['menu_item_weight'] = [
      '#type' => 'textfield',
      '#title' => 'Menu Weight',
      '#description' => $this->t('Weight to be used for Menu Item.'),
      '#default_value' => $config->get('menu_item_weight'),
    ];

    $form['node_edit_option_title'] = [
      '#type' => 'textfield',
      '#title' => 'Option Title',
      '#description' => $this->t('Label to be used for checkbox on node.'),
      '#default_value' => $config->get('node_edit_option_title'),
    ];

    $form['enabled_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => 'Enabled Content Types',
      '#description' => $this->t('This dynamic menu item will be available on enabled content types'),
      '#options' => $content_types,
      '#default_value' => $config->get('enabled_content_types'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $nid = $form_state->getValue('menu_item_node_id_link');
    $this->configFactory->getEditable('dynamic_menu_item.settings')
      ->set('menu_item_parent', $form_state->getValue('menu_item_parent'))
      ->set('node_edit_option_title', $form_state->getValue('node_edit_option_title'))
      ->set('menu_item_title', $form_state->getValue('menu_item_title'))
      ->set('menu_item_description', $form_state->getValue('menu_item_description'))
      ->set('menu_item_node_id_link', $nid)
      ->set('menu_item_weight', $form_state->getValue('menu_item_weight'))
      ->set('enabled_content_types', $form_state->getValue('enabled_content_types'))
      ->save();

    if ($nid > 0) {
      dynamic_menu_item_update_dynamic_menu_item($nid);
    }
  }

}

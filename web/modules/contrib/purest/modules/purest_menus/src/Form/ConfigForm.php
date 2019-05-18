<?php

namespace Drupal\purest_menus\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManager;

/**
 * Class ConfigForm.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Constructs a new RecaptchaConfigForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, EntityTypeManagerInterface $entity_type_manager, EntityFieldManager $entity_field_manager) {
    parent::__construct($config_factory, $state);
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->fields = $this->entityFieldManager
      ->getFieldDefinitions('menu_link_content', 'content');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'purest_menus.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'purest_menus_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('purest_menus.settings');
    $this->entityStorage = $this->entityTypeManager->getStorage('menu');

    $form['menus_heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Menus Resource'),
    ];

    $form['menus_description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Search and select menus that should be returned by the menus resource.'),
    ];

    $form['menus'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'menu',
      '#title' => $this->t('Menus'),
      '#description' => $this->t('Type to search for menus, separate each menu with a comma.'),
      '#tags' => TRUE,
    ];

    $menu_ids = $config->get('menus');

    if ($menu_ids) {
      $default_value = [];

      foreach ($menu_ids as $menu_id) {
        $menu = $this->entityStorage->load($menu_id['target_id']);

        if ($menu) {
          $default_value[] = $menu;
        }
      }

      $form['menus']['#default_value'] = $default_value;
    }

    $form['fields_table_heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Menu Link Field Settings'),
    ];

    $form['fields_table_description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Use this form to customize or exclude the output of each menu item field.'),
    ];

    $form['fields_table'] = [
      '#type' => 'field_group',
      '#title' => $this->t('Menu Normalizer'),
    ];

    $form['fields_table']['menu_item_fields'] = [
      '#title' => $this->t('Menu Item Fields'),
      '#type' => 'table',
      '#sticky' => TRUE,
      '#header' => [
        $this->t('Name'),
        $this->t('Label'),
        $this->t('Type'),
        $this->t('Custom Label'),
        $this->t('Hide if Empty'),
        $this->t('Exclude'),
      ],
    ];

    $values = $config->get('menu_item_fields');

    foreach ($this->fields as $field_name => $field_definition) {
      $form['fields_table']['menu_item_fields'][$field_name]['name'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $field_definition->getLabel(),
      ];

      $form['fields_table']['menu_item_fields'][$field_name]['label'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $field_name,
      ];

      $form['fields_table']['menu_item_fields'][$field_name]['type'] = [
        '#type' => 'textfield',
        '#disabled' => TRUE,
        '#size' => 30,
        '#default_value' => $field_definition->getType(),
      ];

      $form['fields_table']['menu_item_fields'][$field_name]['custom_label'] = [
        '#type' => 'textfield',
        '#size' => 30,
        '#default_value' => NULL !== $values[$field_name]['custom_label'] ?
        $values[$field_name]['custom_label'] : '',
      ];

      $form['fields_table']['menu_item_fields'][$field_name]['hide_empty'] = [
        '#type' => 'checkbox',
        '#default_value' => NULL !== $values[$field_name]['hide_empty'] ?
        $values[$field_name]['hide_empty'] : 0,
      ];

      $form['fields_table']['menu_item_fields'][$field_name]['exclude'] = [
        '#type' => 'checkbox',
        '#default_value' => NULL !== $values[$field_name]['exclude'] ?
        $values[$field_name]['exclude'] : 0,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->configFactory->getEditable('purest_menus.settings');
    $config->set('menus', $form_state->getValue('menus'));
    $config->set('menu_item_fields', $form_state->getValue('menu_item_fields'));
    $config->save();
  }

}

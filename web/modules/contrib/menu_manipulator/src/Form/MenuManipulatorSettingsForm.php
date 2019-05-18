<?php

namespace Drupal\menu_manipulator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure custom settings for Menu Manipulators.
 */
class MenuManipulatorSettingsForm extends ConfigFormBase {

  /**
   * The list of existing Menus (config entities).
   *
   * @var array
   */
  protected $menus;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_manipulator_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'menu_manipulator.settings',
    ];
  }

  /**
   * Constructs a \Drupal\menu_manipulator\Form\MenuManipulatorSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity service.
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *   The language manager handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              ConfigEntityStorage $menu_storage,
                              ConfigurableLanguageManagerInterface $language_manager) {
    parent::__construct($config_factory);

    $this->menus = $menu_storage->loadMultiple();
    $this->languageManager = $language_manager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    $menu_storage = $entity_type_manager->getStorage('menu');
    return new static(
      $container->get('config.factory'),
      $menu_storage,
      $container->get('language_manager')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('menu_manipulator.settings');
    
    $menus_options = [];
    foreach ($this->menus as $menu) {
      $menus_options[$menu->id()] = $menu->label();
    }

    // Quick intro.
    $form['intro'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . t('Configure custom Menu Manipulator settings here.') . '</p>',
      '#weight' => 0,
    ];

    // Global settings.
    $form['global'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Global'),
      '#weight' => 0,
    ];
    $form['global']['preprocess_menus_title'] = [
      '#type' => 'checkbox',
      '#title' => t("Add menus' title in Twig"),
      '#description' => t('You can then use {{ menu_title }} in your menu.html.twig files.'),
      '#default_value' => $config->get('preprocess_menus_title'),
      '#weight' => 0,
    ];
    // Language settings.
    $form['language'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Language'),
      '#weight' => 1,
    ];
    $form['language']['preprocess_menus_language'] = [
      '#type' => 'checkbox',
      '#title' => t("Automatically filter menus by current's user language"),
      '#default_value' => $config->get('preprocess_menus_language'),
      '#weight' => 1,
    ];
    $form['language']['preprocess_menus_language_list'] = [
      '#type' => 'checkboxes',
      '#options' => $menus_options,
      '#title' => t("Select menus to be filtered by language"),
      '#description' => t("If none selected, all menus will be filtered by language."),      
      '#default_value' => !empty($config->get('preprocess_menus_language_list')) ? $config->get('preprocess_menus_language_list') : [],
      '#states' => [
        'visible' => [
          ':input[name="preprocess_menus_language"]' => ['checked' => TRUE],
        ],
      ],      
      '#weight' => 1,
    ];
    
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $exclude = ['submit', 'form_build_id', 'form_token', 'form_id', 'op'];
    $config = \Drupal::configFactory()->getEditable('menu_manipulator.settings');
    foreach ($form_state->getValues() as $key => $data) {
      if (!in_array($key, $exclude)) {
        $config->set($key, $data);
      }
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}

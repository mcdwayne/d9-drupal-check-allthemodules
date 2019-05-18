<?php

namespace Drupal\mmenu_simple\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\system\Entity\Menu;

/**
 * Provides a 'MmenuSimple' block.
 *
 * @Block(
 *  id = "mmenu_simple",
 *  admin_label = @Translation("Mmenu simple"),
 * )
 */
class MmenuSimple extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'basic_settings__trigger_text' => '',
      'basic_settings__trigger_icon' => '',
      'basic_settings__mmenu_source' => [],
      'options__navbar__title' => 'Menu',
      'core_addons__off_canvas__page_selector' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $menus = array();
    $menu_types = Menu::loadMultiple();

    if (!empty($menu_types)) {
      foreach ($menu_types as $menu_name => $menu) {
        $menus[$menu_name] = $menu->label();
      }
      asort($menus);
    }

    $form['basic_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic settings'),
      '#description' => $this->t(''),
    ];
    $form['basic_settings']['trigger_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trigger text'),
      '#description' => $this->t(''),
      '#default_value' => $this->configuration['basic_settings__trigger_text'],
    ];
    $form['basic_settings']['trigger_icon'] = [
      '#title' => $this->t('Trigger icon'),
      '#type' => 'textarea',
      '#description' => $this->t('Icon to display on the trigger. Example: @example', [
        '@example' => '<span class="icon-hamburger"></span>',
      ]),
      '#maxlength' => 255,
      '#default_value' => $this->configuration['basic_settings__trigger_icon'],
    ];
    $form['basic_settings']['mmenu_source'] = [
      '#type' => 'select',
      '#title' => $this->t('MMenu source'),
      '#options' => $menus,
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $this->configuration['basic_settings__mmenu_source'] ? $this->configuration['basic_settings__mmenu_source'] : FALSE,
      '#required' => TRUE,
    ];

    // Options.
    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Options'),
      '#description' => $this->t('The jQuery.mmenu plugin provides a set of options for customizing your menu.'),
    ];
    $form['options']['navbar__title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Navbar title'),
      '#description' => $this->t('The title above the panels. For the main panel it defaults to "Menu", for subpanels it defaults to the text in its parent menu item.'),
      '#default_value' => $this->configuration['basic_settings__trigger_text'],
    ];

    // Core addons.
    $form['core_addons'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Core add-ons'),
      '#description' => $this->t('This add-ons are in mmenu core and enabled by default.'),
    ];
    $form['core_addons']['off_canvas'] = [
      '#type' => 'fieldset',
      '#title' => 'Off-canvas',
      '#description' => $this->t('The "offCanvas" add-on enables the menu to be opened as an off-canvas menu. It is included in the jquery.mmenu .js and .css files and turned on by default.'),
    ];
    $form['core_addons']['off_canvas']['page_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page selector'),
      '#description' => $this->t(''),
      '#default_value' => $this->configuration['core_addons__off_canvas__page_selector'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

    foreach ($values['basic_settings'] as $key => $value) {
      $this->configuration['basic_settings__' . $key] = $value;
    }

    foreach ($values['options'] as $key => $value) {
      $this->configuration['options__' . $key] = $value;
    }

    foreach ($values['core_addons'] as $addon_name => $addon_settings) {
      foreach ($addon_settings as $setting_name => $setting_value) {
        $this->configuration['core_addons__' . $addon_name . '__' . $setting_name] = $setting_value;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Set drupalSettings.
    $drupal_settings = [];
    $drupal_settings['options__navbar__title'] = $this->configuration['options__navbar__title'];
    $drupal_settings['core_addons__off_canvas__page_selector'] = $this->configuration['core_addons__off_canvas__page_selector'];

    // Trigger wrapper element.
    $build['trigger'] = [
      '#type' => 'container',
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => [
            'class' => ['mmenu-trigger'],
          ],
        ],
      ],
      '#attached' => [
        'library' => [
          'mmenu_simple/behaviors',
          'mmenu_simple/mmenu',
        ],
        'drupalSettings' => [
          'mmenu_simple' => [
            'mmenu' => $drupal_settings,
          ]
        ]
      ],
    ];
    // Display icon, if any.
    if ($this->configuration['basic_settings__trigger_icon']) {
      $build['trigger']['icon'] = [
        '#type' => 'markup',
        '#markup' => $this->configuration['basic_settings__trigger_icon'],
        '#prefix' => '<span class="mmenu-trigger__icon">',
        '#suffix' => '</span>',
      ];
      $build['#theme_wrappers']['container']['#attributes']['class'][] = 'has-icon';
    }
    // Display text, if any.
    if ($this->configuration['basic_settings__trigger_text']) {
      $build['trigger']['text'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['mmenu-trigger__text'],
        ],
        '#value' => $this->t($this->configuration['basic_settings__trigger_text']),
      ];
      $build['#theme_wrappers']['container']['#attributes']['class'][] = 'has-text';
    }

    // Display menu.
    $menu_tree_parameters = new MenuTreeParameters();
    $mmenu_source = $this->configuration['basic_settings__mmenu_source'];
    $tree = \Drupal::menuTree()->load($mmenu_source, $menu_tree_parameters);
    $build['mmenu'] = \Drupal::menuTree()->build($tree);
    $build['mmenu']['#theme'] = 'mmenu_template';


    return $build;
  }
}

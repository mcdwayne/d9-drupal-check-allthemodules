<?php

namespace Drupal\responsive_menu_combined\Form;

/**
 * @file
 * Contains \Drupal\simple\Form\SimpleConfigForm.
 */

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for responsive menu combined form.
 */
class ResponsiveMenuCombinedForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'responsive_menu_combined_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Set the form.
    $form = parent::buildForm($form, $form_state);

    // Get the config from responsive menu combined.
    $config = $this->config('responsive_menu_combined.settings');

    // Get the currently set menus.
    $set_menus = $config->get('menus');

    // Get the display parent title config value.
    $display_parent_title = $config->get('display_parent_title');

    // Get the html tags config value.
    $html_tags = $config->get('html_tags');

    // Get all menus on the site.
    $all_menus = entity_load_multiple('menu');

    // Set the table to be used in the admin settings.
    $form['responsive_menu_combined'] = [
      '#type' => 'table',
      '#header' => [
        t('Menu'),
        t('Enabled/disabled'),
        t('Visible Menu Name'),
      ],
      '#empty' => t('There are no items yet.'),
      // TableDrag: Each array value is a list of callback arguments for
      // drupal_add_tabledrag(). The #id of the table is automatically prepended
      // if there is none, an HTML ID is auto-generated.
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'responsive_menu_combined-order-weight',
        ],
      ],
    ];

    // Step through each of the all menus and set the config.
    foreach ($all_menus as $menu) {

      // Set the table as draggable.
      $form['responsive_menu_combined'][$menu->get('id')]['#attributes']['class'][] = 'draggable';

      // Set the name of the menu.
      $form['responsive_menu_combined'][$menu->get('id')]['menu'] = [
        '#plain_text' => $menu->get('label'),
      ];

      // Set the checkbox for enabled/disabled.
      $form['responsive_menu_combined'][$menu->get('id')]['enabled'] = [
        '#type' => 'checkbox',
        '#default_value' => isset($set_menus[$menu->get('id')]) ? 1 : 0,
      ];

      // Set the visible name, this is the title of the menu.
      $form['responsive_menu_combined'][$menu->get('id')]['visible_name'] = [
        '#type' => 'textfield',
        '#default_value' => isset($set_menus[$menu->get('id')]) ? $set_menus[$menu->get('id')] : $menu->get('label'),
      ];
    }

    // Set fieldset for advanced options.
    $form['advanced_options'] = array(
        '#type' => 'fieldset',
        '#title' => t('Advanced Options'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
    );

    // Set the config to display parent title or not.
    $form['advanced_options']['display_parent_title'] = array(
        '#type' => 'checkbox',
        '#title' => t('Display parent title'),
        '#description' => t('When you are inside a sub-navigation pane, display the parents title below the back button.'),
        '#default_value' => isset($display_parent_title) ? $display_parent_title : 0,
    );

    // Set the html tags to be used.
    $form['advanced_options']['html_tags'] = array(
        '#type' => 'select',
        '#title' => 'Menu HTML tag',
        '#options' => array(
            'h1' => '<h1>',
            'h2' => '<h2>',
            'h3' => '<h3>',
            'h4' => '<h4>',
            'h5' => '<h5>',
            'h6' => '<h6>',
        ),
        '#default_value' => isset($html_tags) ? $html_tags : 'h2',
        '#description' => t('Select the HTML tag you would like to use for the menu titles.'),
    );

    // Set the help text at the bottom of the form.
    $form['help_text'] = array(
        '#markup' => '<div>' . t('Note: Empty menus will be hidden.') . '</div>',
    );

    // Set the submit settings.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save changes'),
      // TableSelect: Enable the built-in form validation for #tableselect for
      // this form button, so as to ensure that the bulk operations form cannot
      // be submitted without any selected items.
      '#tableselect' => TRUE,
    ];

    // Return the form settings.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get all of the values from the responsive menu combined admin settings.
    $rmc = $form_state->getValue('responsive_menu_combined');

    // Step through each of the admin settings and set the enabled
    // menus and the titles.
    foreach ($rmc as $menu_id => $values) {
      if ($values['enabled']) {
        $menus[$menu_id] = $values['visible_name'];
      }
    }

    // Set the configuration.
    $config = $this->config('responsive_menu_combined.settings');
    $config->set('menus', $menus);
    $config->set('display_parent_title', $form_state->getValue('display_parent_title'));
    $config->set('html_tags', $form_state->getValue('html_tags'));
    $config->save();

    // Return back to the form.
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {

    // Return the settings file that is going to be used.
    return [
      'responsive_menu_combined.settings',
    ];
  }

}

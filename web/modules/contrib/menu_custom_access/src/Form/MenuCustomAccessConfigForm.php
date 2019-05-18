<?php

/**
 * @file
 * Contains \Drupal\menu_custom_access\Form\MenuCustomAccessConfigForm.
 */

namespace Drupal\menu_custom_access\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\ContextualLinkManagerInterface;
use Drupal\Core\Menu\LocalActionManagerInterface;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;

class MenuCustomAccessConfigForm extends ConfigFormBase {
  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'menu_custom_access_config_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    //remove the authenticated user role from the results
    $site_user_roles = user_role_names(TRUE);
    $site_user_role_options = [];
    foreach($site_user_roles as $site_user_role => $v) {
        $site_user_role_options[$site_user_role] = $v;
    }

    //unset the authenticated and administrator roles
    unset($site_user_role_options['authenticated']);
    unset($site_user_role_options['administrator']);

    // Form constructor
    $form = parent::buildForm($form, $form_state);
    // Default settings
    $config = $this->config('menu_custom_access.settings');

    //Form Render
    $form['menu_custom_access_buildform'] = array(
      '#type' => 'menu_custom_access_buildform',
    );
    $form['menu_custom_access_roles'] = array(
      '#type' => 'fieldset',
      '#title' => t('Roles:'),
    );
    $form['menu_custom_access_roles']['roles'] = array(
      '#type' => 'checkboxes',
      '#options' => $site_user_role_options,
      '#title' => $this->t('Allow menu and route access to role(s):'),
      '#default_value' => $config->get('menu_custom_access.roles'),
      '#description' => 'Please specify roles that should have menu and access'
    );
    $form['menu_custom_access_menus'] = array(
      '#type' => 'fieldset',
      '#title' => t('Menus:'),
    );
    $form['menu_custom_access_menus']['restrict_add_menus'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Restrict adding new menus'),
      '#default_value' => $config->get('menu_custom_access.restrict_add_menus'),
    );
    $form['menu_custom_access_menus']['menus'] = array(
      '#type' => 'checkboxes',
      '#options' => menu_ui_get_menus(),
      '#title' => $this->t('Restrict Menu Access From:'),
      '#default_value' => $config->get('menu_custom_access.menus'),
    );
    $form['menu_custom_access_routes'] = array(
      '#type' => 'fieldset',
      '#title' => t('Routes:'),
    );
    $form['menu_custom_access_routes']['routes'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Restrict Route Path Access To:'),
      '#default_value' => $config->get('menu_custom_access.routes') ?: array(
          'please_set_custom_roles' => 'Please Set Custom Roles in Order to Use Module', 
      ),
      '#description' => 'Put each route path on its own line',
    );
    $form['menu_custom_access_routes']['route_debug'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Click to get route debug on pages'),
      '#default_value' => $config->get('menu_custom_access.route_debug'),
    );
    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}.
   */
  // public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('menu_custom_access.settings');

    // Set the selected role config
    $submitted_roles = $form_state->getValue('roles');
    $set_roles = [];
    if(!empty($submitted_roles)) {
      foreach ($submitted_roles as $k => $v) {
        if(!is_numeric($v)) {
          $set_roles[] = $v;
        }
      }
    }
    // Menus
    $config->set('menu_custom_access.roles', $set_roles);
    $config->set('menu_custom_access.menus', $form_state->getValue('menus'));
    $config->set('menu_custom_access.restrict_add_menus', $form_state->getValue('restrict_add_menus'));
    $config->set('menu_custom_access.routes', $form_state->getValue('routes'));
    $config->set('menu_custom_access.route_debug', $form_state->getValue('route_debug'));

    $config->save();

    // Rebuild the menu cache
    menu_cache_clear_all();
    \Drupal::service('plugin.manager.menu.link')->rebuild();
    \Drupal::service('plugin.manager.menu.contextual_link')->clearCachedDefinitions();
    \Drupal::service('plugin.manager.menu.local_task')->clearCachedDefinitions();
    \Drupal::service('plugin.manager.menu.local_action')->clearCachedDefinitions();
     
    return parent::submitForm($form, $form_state);
  }

  /**
   * This allows the form to modify settings data
   */
  protected function getEditableConfigNames() {
    return [
      'menu_custom_access.settings',
    ];
  }
}

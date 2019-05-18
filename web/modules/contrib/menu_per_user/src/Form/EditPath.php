<?php

namespace Drupal\menu_per_user\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class EditPath extends FormBase {

  public function getFormId() {
    return 'menu_per_user_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $menu_path = \Drupal::request()->query->get('menu_path');
    // Check for menu path existence.
    $path_check = menu_per_user_check_path($menu_path);
    // Check for menu path existence for anonymous user.
    $anonymous_check = menu_per_user_anonymous_path($menu_path);
    // Check for menu path existence for other user(s).
    $other_check = menu_per_user_other_path($menu_path);
    $users_default = '';
    if ($anonymous_check) {
      $default_value = array(1);
    }
    if ($other_check) {
      $users_default = array_keys($other_check);
    }
    if ($path_check) {
      $form['menu_path'] = array(
        '#type' => 'textfield',
        '#title' => 'Menu Path',
        '#required' => TRUE,
        '#default_value' => $menu_path,
        '#disabled' => TRUE,
      );
      // Fetch User Names.
      $form['menu_visibility']['fieldset'] = array(
        '#type' => 'fieldset',
        '#title' => t('Menu Visibility settings for User(s)'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#group' => 'menu_visibility',
        '#weight' => 10,
      );
      $users = _menu_per_user_fetch_names();
      $form['menu_visibility']['fieldset']['users'] = array(
        '#prefix' => '<div class="users-checkbox">',
        '#type' => 'checkboxes',
        '#title' => t('Select User(s)'),
        '#options' => $users,
        '#default_value' => isset($users_default) ? $users_default : array(),
        '#suffix' => '</div>',
      );
      $form['menu_visibility']['fieldset']['anonymous'] = array(
        '#type' => 'checkboxes',
        '#markup' => '<b>For Anonymous User.</b>',
        '#options' => array('1' => 'Anonymous User.'),
        '#default_value' => isset($default_value) ? $default_value : array(),
      );
      $form['menu_visibility']['fieldset']['markup'] = array(
        '#markup' => t('Show/hide this menu link only for the selected user(s). If you select no user, the menu link will be shown to all user(s).'),
      );
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save'),
      );
      return $form;
    }
    // Error if not present.
    else {
      drupal_set_message(t('Menu Path Specified is not correct to update'), 'error');
    }
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $message = 0;
    $users = $values['users'];
    $menu_path = $values['menu_path'];
    $anonymous = $values['anonymous']['1'];
    // Delete path and update after that.
    db_delete('user_menu_visibility')
      ->condition('menu_path', $menu_path, '=')
      ->execute();
    // delete empty menu paths
    db_delete('user_menu_visibility')
      ->isNull('menu_path')
      ->execute();
    // Insert for anonymous user.
    if (($anonymous == 1)) {
      $query = db_insert('user_menu_visibility');
      $query->fields(array('menu_path', 'user_id'));
      $query->values(array('menu_path' => $menu_path, 'user_id' => '0'));
      $query->execute();
    }
    if ($users) {
      $query = db_insert('user_menu_visibility');
      $query->fields(array('menu_path', 'user_id'));
      foreach ($users as $user_id) {
        if ($menu_path && $user_id > 0) {
          $query->values(array('menu_path' => $menu_path, 'user_id' => $user_id));
          $message = 1;
        }
      }
      $query->execute();
    }
    if ($message == 1) {
      drupal_set_message(t("The path <b>'@link_path'</b> saved.", array('@link_path' => $menu_path)), 'status');
    }
    else {
      drupal_set_message(t("The path <b>'@link_path'</b> not saved, as no user(s) selected. So it will display to all user(s)", array('@link_path' => $menu_path)), 'warning');
    }
    menu_cache_clear_all();
  }

}

<?php

namespace Drupal\menu_per_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * {@inheritdoc}.
 */
class MenuPerUserForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'menu_per_user_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Menu Visibility settings.
    $form['menu_visibility']['fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => t('Menu Visibility settings for User(s)'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#group' => 'menu_visibility',
      '#weight' => 10,
    );
    $form['menu_visibility']['fieldset']['path'] = array(
      '#type' => 'textfield',
      '#title' => t('Menu Path'),
      '#attributes' => array(
        'placeholder' => t('Enter Path'),
      ),
      '#required' => TRUE,
      '#description' => t('This can be an internal Drupal path such as node/add.'),
    );
    // Fetch User Names.
    $users = _menu_per_user_fetch_names();
    $form['menu_visibility']['fieldset']['users'] = array(
      '#prefix' => '<div class="users-checkbox">',
      '#type' => 'checkboxes',
      '#title' => t('Select User(s)'),
      '#options' => $users,
      '#suffix' => '</div>',
    );
    $form['menu_visibility']['fieldset']['anonymous'] = array(
      '#type' => 'checkboxes',
      '#markup' => '<b>' . t('For Anonymous User.') . '</b>',
      '#options' => array('1' => 'Anonymous User.'),
      '#default_value' => isset($default_value) ? $default_value : array(),
    );
    $form['menu_visibility']['fieldset']['markup'] = array(
      '#markup' => t('Show/hide this menu link only for the selected user(s). If you select no user, the menu link will be shown to all user(s).'),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );
    $form['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset'),
    );
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Reset Form.
    if ($values['op']->render() == 'Reset') {
      url_redirect_redirect(\Drupal::url('menu_per_user.form'));
    }
    // Validate only in submit click.
    if ($values['op']->render() == 'Submit') {
      $path = $values['path'];
      // Validate internal path.
      if (!\Drupal::service('path.validator')->isValid($path)) {
        $form_state->setErrorByName('path', $this->t("The path '@link_path' not a valid path.", array('@link_path' => $path)));
      }
      // Check path already exists.
      $connection = \Drupal::database();
      $path_check = $connection->select('user_menu_visibility', 'm')
        ->fields('m', ['menu_path'])
        ->condition('menu_path', $path, '=')
        ->execute();
      $path_result = $path_check->fetchField();
      if ($path_result) {
        $form_state->setErrorByName('path', $this->t("The path '@link_path' already exists.", array('@link_path' => $path)));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = \Drupal::database();
    $values = $form_state->getValues();
    $message = 0;
    $users = $values['users'];
    $menu_path = $values['path'];
    $anonymous = $values['anonymous']['1'];
    $url_object = \Drupal::service('path.validator')->getUrlIfValid($menu_path);
    $route_name = $url_object->getRouteName();
    // Insert for anonymous user.
    if (($anonymous == 1)) {
      $query = $connection->insert('user_menu_visibility');
      $query->fields(['menu_path', 'user_id', 'menu_name']);
      $query->values(
        [
          'menu_path' => $menu_path,
          'user_id' => '0',
          'menu_name' => $route_name,
        ]
      );
      $query->execute();
    }
    if ($users) {
      $query = $connection->insert('user_menu_visibility');
      $query->fields(['menu_path', 'user_id', 'menu_name']);
      foreach ($users as $user_id) {
        if ($menu_path && $user_id > 0) {
          $query->values(
            [
              'menu_path' => $menu_path,
              'user_id' => $user_id,
              'menu_name' => $route_name,
            ]
          );
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

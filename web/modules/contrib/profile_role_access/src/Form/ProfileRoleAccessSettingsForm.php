<?php

namespace Drupal\profile_role_access\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Define the access matrix.
 */
class ProfileRoleAccessSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'profile_role_access_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array(
      'profile_role_access.settings',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();

    // Get a list fo roles that can profile (remove anonymous)
    $roles_with_profiles = user_role_names();
    if (isset($roles_with_profiles['anonymous'])) {
      unset($roles_with_profiles['anonymous']);
    }

    // Get the access permission array.
    $matrix = $this->config('profile_role_access.settings')->get('access_matrix');

    if ((!is_array($matrix)) || (count($matrix) == 0)) {
      $matrix = array();
    }

    // Set up the table header.
    $form['profile_role_access_permissions'] = array(
      '#type' => 'table',
      '#header' => array('Can view: ') + $roles_with_profiles,
    );

    // Build the table.
    foreach (user_role_names() as $rolefrom => $displaynamefrom) {

      $form['profile_role_access_permissions'][$rolefrom]['roleto'] = array(
        '#markup' => $displaynamefrom . ' ' . t('(current user)'),
      );

      foreach ($roles_with_profiles as $roleto => $displaynameto) {

        // Load the default value from the matrix.
        if (isset($matrix[$rolefrom][$roleto])) {
          $default_val = $matrix[$rolefrom][$roleto];
        }
        else {
          $default_val = 0;
        }

        // Add the checkbox.
        $form['profile_role_access_permissions'][$rolefrom][$roleto] = array(
          '#type' => 'checkbox',
          '#title' => $displaynameto,
          '#title_display' => FALSE,
          '#default_value' => $default_val,
        );
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('profile_role_access.settings')
      ->set('access_matrix', $form_state->getValue('profile_role_access_permissions'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

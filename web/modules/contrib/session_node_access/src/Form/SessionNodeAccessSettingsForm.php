<?php

namespace Drupal\session_node_access\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SessionNodeAccessSettingsForm
 * @package Drupal\session_node_access\Form
 */
class SessionNodeAccessSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'session_node_access_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'session_node_access.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('session_node_access.settings');

    // Fieldsets.
    $form['moduleinfo'] = [
      '#markup' => t('On this page you can grant additional permissions to users after they create a node.
      Consider this use case: Content created by anonymous users is set to be not published until a mod reviews it.
      With this module the user gets to view/edit/delete their freshly created content without it to be publicly
      accessible.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $form['node_types_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Restrict by content type'),
      '#description' => t('Grant per-session node permissions to certain users who create nodes of the following content types.
      If a user creates a node of the checked content type, they will get access to it until their session expires.
      Check at least one element.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['user_roles_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Restrict by user roles'),
      '#description' => t('Grant per-session node permissions to all users assigned to the following roles.
      <strong>Note:</strong> In most cases checking roles other than \'anonymous\' won\'t be necessary because of the available \'View own unpublished\'
      options in the permissions tab. Check at least one element.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['operations_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Grant these permissions'),
      '#description' => t('Said users will be granted the following permissions to nodes they create.
      These permissions will expire along with the user\'s session. Check at least one element.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    // Display content type settings.
    $node_types = node_type_get_names();
    $default_value = [];
    foreach ($node_types as $machine_name => $human_name) {
      if (!empty($config->get('node_types')[$machine_name])) {
        $default_value[] = $machine_name;
      }
    }
    $form['node_types_fieldset']['node_types'] = [
      '#type' => 'checkboxes',
      '#options' => $node_types,
      '#default_value' => $default_value,
    ];

    // Display user role settings.
    $default_value = [];
    $options = [];
    foreach (user_roles() as $role_id => $role) {
      if (!empty($config->get('user_roles')[$role_id])) {
        $default_value[] = $role_id;
      }
      $options[$role_id] = $role_id;
    }
    $form['user_roles_fieldset']['user_roles'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $default_value,
    ];

    // Display operation settings.
    $operations = [
      'view' => t('View'),
      'update' => t('Update'),
      'delete' => t('Delete'),
    ];
    $default_value = [];
    foreach ($operations as $operation => $human_operation) {
      if (!empty($config->get('operations')[$operation])) {
        $default_value[] = $operation;
      }
    }
    $form['operations_fieldset']['operations'] = [
      '#type' => 'checkboxes',
      '#options' => $operations,
      '#default_value' => $default_value,
    ];

    // Display publishing setting.
    $form['published_fieldset']['published'] = [
      '#title' => t('Take effect only on published nodes'),
      '#description' => t('Usually this is unchecked, as most use cases for this module require it to give temporary
      permissions to users to nodes they create but which are still unpublished by the moderator.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('published'),
    ];

    // Display change ownership setting.
    $form['change_ownership'] = [
      '#title' => t('Change ownership of nodes to newly registered user'),
      '#description' => t('As soon as an anonymous user register an account, grant that account ownership of nodes the user had session access to.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('change_ownership')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    /** @var Config $config */
    $config = \Drupal::service('config.factory')->getEditable('session_node_access.settings');

    foreach (['node_types', 'user_roles', 'operations'] as $setting_name) {
      $value = $form_state->getValue($setting_name);
      foreach ($value as $i => $setting) {
        $value[$i] = (int) !empty($setting);
      }
      $config->set($setting_name, $value);
    }
    $config
      ->set('published', (int) $form_state->getValue('published'))
      ->set('change_ownership', (int) $form_state->getValue('change_ownership'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}

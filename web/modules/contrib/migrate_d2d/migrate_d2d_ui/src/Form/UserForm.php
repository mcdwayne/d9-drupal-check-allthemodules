<?php

namespace Drupal\migrate_d2d_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Simple wizard step form.
 */
class UserForm extends DrupalMigrateForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_d2d_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $connection = $this->connection($form_state);
    $form['overview'] = [
      '#markup' => $this->t('User accounts other than the admin account (user ID 1) may be imported to this site.'),
    ];
    $form['#tree'] = TRUE;
    if (!isset($this->userCount)) {
      $this->userCount = $connection->select('users', 'u')
        ->condition('uid', 1, '>')
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    $form['users'] = [
      '#markup' => $this->t('Number of users available to be migrated from your Drupal @version site: @count',
        ['@version' => $cached_values['version'], '@count' => $this->userCount]),
    ];
    $form['do_migration'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Perform import of users'),
      '#default_value' => TRUE,
    ];

    // Build select list from destination roles.
    $base_options = [
      '-1' => $this->t('--Do not import--'),
      '0' => $this->t('--Create role--'),
    ];
    $role_options = [];
    foreach (user_role_names(TRUE) as $rid => $role) {
      $role_options[$rid] = $role;
    }

    // Go through the non-trivial source roles.
    $result = $connection->select('role', 'r')
      ->fields('r', ['rid', 'name'])
      ->condition('name', ['anonymous user', 'authenticated user'], 'NOT IN')
      ->execute();
    $source_roles = [];
    foreach ($result as $row) {
      $source_roles[$row->rid] = $row->name;
    }

    if (!empty($source_roles)) {
      // Description
      $form['role_overview'] = [
        '#markup' => $this->t('For each user role on the legacy site, choose whether to ignore that role, to create it on this site, or to assign a different role to users with that legacy role.'),
      ];
      foreach ($source_roles as $rid => $name) {
        $options = $base_options + $role_options;
        // If we have a match on role name, default the mapping to that match
        // and remove the option to create a new role of that name.
        if (in_array($name, $role_options)) {
          $default_value = $name;
          unset($options['0']);
        }
        else {
          $default_value = '-1';
        }
        $count = $connection->select('users_roles', 'ur')
          ->condition('rid', $rid)
          ->countQuery()
          ->execute()
          ->fetchField();
        $title = $this->t('@name (@count)', [
          '@name' => $name,
          '@count' => $this->getStringTranslation()->formatPlural($count, '1 user', '@count users')
        ]);
        $form['role'][$name] = [
          '#type' => 'select',
          '#title' => $title,
          '#options' => $options,
          '#default_value' => $default_value,
        ];
      }
    }
    else {
      $form['role_overview'] = [
        '#markup' => $this->t('There are no user roles in the source site that are not already in the destination site'),
      ];
    }

    $options = ['authenticated user' => 'authenticated user'] + $role_options;
    $form['default_role'] = [
      '#type' => 'select',
      '#title' => $this->t('Default role'),
      '#description' => $this->t('Choose the role to assign to any user accounts who had none of the above roles on the legacy site'),
      '#options' => $options,
      '#default_value' => AccountInterface::AUTHENTICATED_ROLE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    if ($form_state->getValue('do_migration')) {
      $cached_values['user_migration'] = TRUE;
      $cached_values['role_mappings'] = $form_state->getValue('role');
      $cached_values['default_role'] = $form_state->getValue('default_role');
      // Map "do not import" roles to the default role, and remove roles
      // to be created so they get imported naturally.
      foreach ($cached_values['role_mappings'] as $source_role => $destination_role) {
        if ($destination_role == '-1') {
          $cached_values['role_mappings'][$source_role] = $cached_values['default_role'];
        }
        elseif ($destination_role == '0') {
          unset($cached_values['role_mappings'][$source_role]);
        }
      }
      // Default role needs to be a rid.
      $cached_values['default_role'] = array_search($cached_values['default_role'], user_role_names());
    }
    else {
      $cached_values['user_migration'] = FALSE;
    }
    $form_state->setTemporaryValue('wizard', $cached_values);
  }

}

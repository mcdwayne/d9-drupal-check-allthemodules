<?php

namespace Drupal\core_extend\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a trait for accessing changed time.
 */
trait PermissionsFormTrait {

  /**
   * The module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  abstract protected function moduleHandler();

  /**
   * Gets the permissions to display in this form.
   *
   * @return array
   *   An multidimensional associative array of permissions, keyed by the
   *   providing module first and then by permission name.
   */
  abstract protected function getPermissions();

  /**
   * Gets the organization roles to display in this form.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface[]|\Drupal\core_extend\Entity\RoleEntityInterface[]
   *   An array of organization role objects.
   */
  abstract protected function getRoles();

  /**
   * Gets a few basic instructions to show the user.
   *
   * @return array
   *   A render array to display atop the form.
   */
  protected function getInfo() {
    // Format a message explaining the cells with a red x inside them.
    $replace = ['@red_dash' => new FormattableMarkup('<span style="color: #ff0000;">-</span>', [])];
    $message = $this->t('Cells with a @red_dash indicate that the permission is not available for that role.', $replace);

    // We use FormattableMarkup so the 'style' attribute doesn't get escaped.
    return ['red_dash_info' => ['#markup' => new FormattableMarkup("<p>$message</p>", [])]];
  }

  /**
   * {@inheritdoc}
   */
  public function formPermissions(array $form, FormStateInterface $form_state) {
    // Initiate variables.
    $admin_roles = [];
    $roles = $this->getRoles();
    $role_info = [];

    // Sort the roles using the static sort() method.
    uasort($roles, '\Drupal\Core\Config\Entity\ConfigEntityBase::sort');

    // Retrieve information for every role to use further down. We do this to
    // prevent the same methods from being fired (rows * permissions) times.
    foreach ($roles as $role_name => $role) {
      $role_info[$role_name] = [
        'label' => $role->label(),
        'permissions' => $role->getPermissions(),
      ];
      $admin_roles[$role_name] = $role->isAdmin();
    }

    // Render the general information.
    if ($info = $this->getInfo()) {
      $form['info'] = $info;
    }

    $form['#attached']['library'][] = 'organization/permissions';

    // Render the link for hiding descriptions.
    $form['system_compact_link'] = [
      '#id' => FALSE,
      '#type' => 'system_compact_link',
    ];

    // Render the roles and permissions table.
    $form['permissions'] = [
      '#type' => 'table',
      '#header' => [$this->t('Permission')],
      '#id' => 'permissions',
      '#attributes' => ['class' => ['permissions', 'js-permissions']],
      '#sticky' => TRUE,
    ];

    // Create a column with header for every role.
    foreach ($role_info as $info) {
      $form['permissions']['#header'][] = [
        'data' => $info['label'],
        'class' => ['checkbox'],
      ];
    }

    // Setup 'Is absolute' toggle.
    $form['permissions']['is_admin']['#attributes']['class'] = ['color-warning'];
    $form['permissions']['is_admin']['description'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="permission"><span class="title">Role has all permissions</span><div class="description"><em class="permission-warning">Warning: use sparingly.</em><br />Role will have all available permissions by default.</div></div>',
      '#context' => [
        'title' => 'Is admin',
      ],
    ];
    foreach ($role_info as $role_name => $info) {
      $form['permissions']['is_admin'][$role_name] = [
        '#title' => t('Is admin'),
        '#title_display' => 'invisible',
        '#wrapper_attributes' => [
          'class' => ['checkbox'],
        ],
        '#type' => 'checkbox',
        '#default_value' => $roles[$role_name]->isAdmin(),
        '#attributes' => ['class' => ['rid-' . $role_name, 'js-rid-' . $role_name]],
        '#parents' => [$role_name, 'is_admin'],
      ];
    }

    $hide_descriptions = system_admin_compact_mode();
    foreach ($this->getPermissions() as $provider => $permissions) {
      // Start each section with a full width row containing the provider name.
      $form['permissions'][$provider] = [[
        '#wrapper_attributes' => [
          'colspan' => count($roles) + 1,
          'class' => ['module'],
          'id' => 'module-' . $provider,
        ],
        '#markup' => $this->moduleHandler()->getName($provider),
      ],
      ];

      // Then list all of the permissions for that provider.
      foreach ($permissions as $perm => $perm_item) {
        // Create a row for the permission, starting with the description cell.
        $form['permissions'][$perm]['description'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="permission"><span class="title">{{ title }}</span>{% if description or warning %}<div class="description">{% if warning %}<em class="permission-warning">{{ warning }}</em><br />{% endif %}{{ description }}</div>{% endif %}</div>',
          '#context' => [
            'title' => $perm_item['title'],
            'description' => '',
          ],
        ];

        // Show the permission description and warning if toggled on.
        if (!$hide_descriptions) {
          if (array_key_exists('description', $perm_item) && !is_null($perm_item['description'])) {
            $form['permissions'][$perm]['description']['#context']['description'] = $perm_item['description'];
          }
          if (array_key_exists('warning', $perm_item)) {
            // @todo add 'restrict access' warnings
            $form['permissions'][$perm]['description']['#context']['warning'] = $perm_item['warning'];
          }
        }

        // Finally build a checkbox cell for every role.
        foreach ($role_info as $role_name => $info) {
          // Show a checkbox if the permissions is available.
          $form['permissions'][$perm][$role_name] = [
            '#title' => $info['label'] . ': ' . $perm_item['title'],
            '#title_display' => 'invisible',
            '#wrapper_attributes' => [
              'class' => ['checkbox'],
            ],
            '#type' => 'checkbox',
            '#default_value' => in_array($perm, $info['permissions']) ? 1 : 0,
            '#attributes' => ['class' => ['rid-' . $role_name, 'js-rid-' . $role_name]],
            '#parents' => [$role_name, $perm],
          ];
          // Show a column of disabled but checked checkboxes.
          if ($admin_roles[$role_name]) {
            $form['permissions'][$perm][$role_name]['#disabled'] = TRUE;
            $form['permissions'][$perm][$role_name]['#default_value'] = TRUE;
          }

          $form['permissions'][$perm][$role_name]['#states'] = [
            'disabled' => [
              ':input[name="' . $role_name . '[is_absolute]"]' => ['checked' => TRUE],
            ],
          ];
        }
      }
    }

    // Make permissions filterable if permissions_filter is installed.
    if ($this->moduleHandler()->moduleExists('permissions_filter')) {
      permissions_filter_form_alter($form, $form_state, 'user_admin_permissions');
      $form['filters']['search_permission']['#attributes']['data-table'] = '#permissions';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPermissions(array &$form, FormStateInterface $form_state) {
    foreach ($this->getRoles() as $role_name => $role) {
      $permissions = $form_state->getValue($role_name);
      $is_admin = $role->isAdmin();

      // Remove is_admin field and set value.
      if (array_key_exists('is_admin', $permissions)) {
        $is_admin = (!empty($permissions['is_admin']));
        unset($permissions['is_admin']);
      }

      if ($role->isAdmin() || $is_admin !== $role->isAdmin()) {
        $role->setIsAdmin($is_admin)->save();
      }
      else {
        $role->changePermissions($permissions)->trustData()->save();
      }
    }

    drupal_set_message($this->t('The changes have been saved.'));
  }

}

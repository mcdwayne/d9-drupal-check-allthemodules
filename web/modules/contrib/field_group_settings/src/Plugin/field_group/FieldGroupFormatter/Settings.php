<?php

namespace Drupal\field_group_settings\Plugin\field_group\FieldGroupFormatter;

use Drupal\Core\Render\Element;
use Drupal\field_group\FieldGroupFormatterBase;
use Drupal\user\Entity\Role;

/**
 * Plugin implementation of the 'settings' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "settings",
 *   label = @Translation("Settings"),
 *   description = @Translation("Renders a field group as collapsible settings."),
 *   supported_contexts = {
 *     "form",
 *   }
 * )
 */
class Settings extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {

    parent::preRender($element, $rendering_object);

    $element += [
      '#type' => 'field_group_settings',
      '#options' => [
        'attributes' => [
          'class' => $this->getClasses(),
        ],
      ],
      '#access' => $this->isVisible(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = parent::settingsForm();

    $val = $this->getSetting('visible_for_roles');
    $roles = user_role_names(TRUE);
    
    // see if any roles bypass the permission
    $disabled = [];
    $role_objs = Role::loadMultiple(array_keys($roles));
    foreach ($role_objs as $role_id => $role) {
      if ($role->hasPermission('bypass field_group_settings field visibility')) {
        $disabled[$role_id] = $role_id;
        $val[$role_id] = $role_id;
      }
    }

    $form['visible_for_roles'] = [
      '#title' => $this->t('Roles that can view'),
      '#type' => 'checkboxes',
      '#options' => $roles,
      '#default_value' => $val,
      '#weight' => 2,
      '#description' => $this->t('Disabled options are managed by permissions.'),
    ];

    foreach ($disabled as $disabled_opt) {
      $form['visible_for_roles'][$disabled_opt] = [
        '#disabled' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $role_names = user_role_names(TRUE);
    $visible = $this->getSetting('visible_for_roles');

    // add global bypass settings
    $role_objs = Role::loadMultiple(array_keys($role_names));
    foreach ($role_objs as $role_id => $role) {
      if ($role->hasPermission('bypass field_group_settings field visibility')) {
        $visible[$role_id] = 1;
      }
    }

    // map allowed roles to their names
    $allowed_role_names = array_map(function($role_id) use ($role_names) {
      return $role_names[$role_id];
    }, array_keys(array_filter($visible)));

    $summary = [];
    if ($allowed_role_names) {
      $summary[] = $this->t('Visible for: @roles',
        ['@roles' => implode(', ', $allowed_role_names)]
      );
    }

    return $summary;
  }

  protected function isVisible() {
    $current_user = \Drupal::currentUser();
    if ($current_user->hasPermission('bypass field_group_settings field visibility')) {
      return TRUE;
    }
    $user_roles = $current_user->getRoles();
    $visible = $this->getSetting('visible_for_roles');
    if (empty($visible)) {
      return FALSE;
    }
    $allowed = array_filter($visible);
    if (empty($allowed)) {
      return FALSE;
    }
    $match = array_intersect($user_roles, $allowed);
    return (count($match) > 0);
  }

  /**
   * {@inheritdoc}
   */
  protected function getClasses() {
    $classes = ['field-group-settings'];
    $classes = array_merge($classes, parent::getClasses());
    return $classes;
  }
}

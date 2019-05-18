<?php

namespace Drupal\rac_relations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class RoleAccessControlRelationsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rac_relations_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $roles = user_roles();

    $form['table_title'] = [
      '#type' => 'item',
      '#title' => $this->t('Update Grants'),
      '#description' => $this->t('For each row, configure which roles (columns) for which editing is approved.'),
    ];
    $form['role_access'] = [
      '#type' => 'table',
      '#header' => [''],
      '#id' => 'role-access-all',
      '#attributes' => ['class' => ['role-access-all', 'js-role-access-all']],
      '#sticky' => TRUE,
    ];

    foreach ($roles as $role) {
      $form['role_access']['#header'][] = [
        'data' => $role->label(),
        'class' => ['checkbox'],
      ];
    }

    foreach ($roles as $rid => $role) {
      // Fill in default values for the permission.
      $form['role_access'][$rid]['description'] = [
        '#type' => 'inline_template',
        '#template' => '<div class=\'permission\'><span class=\'title\'>{{ title }}</span></div>',
        '#context' => [
          'title' => $role->label(),
        ],
      ];
      foreach ($roles as $srid => $srole) {

        $permission = 'RAC update ' . $srole->id();

        $form['role_access'][$rid][$srid] = [
          '#title' => $role->label() . ' can edit for ' . $srole->label(),
          '#title_display' => 'invisible',
          '#wrapper_attributes' => [
            'class' => ['checkbox'],
          ],
          '#type' => 'checkbox',
          '#default_value' => 0,
          '#attributes' => ['class' => ['rid-' . $srid, 'js-rid-' . $srid]],
          '#parents' => [$rid, $srid],
        ];

        if ($role->isAdmin()) {
          $form['role_access'][$rid][$srid]['#disabled'] = TRUE;
        }

        if ($role->hasPermission($permission)) {
          $form['role_access'][$rid][$srid]['#default_value'] = 1;
        }
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save permissions'),
      '#button_type' => 'primary',
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $roles = user_roles();

    foreach ($roles as $rid => $role) {
      if ($role->isAdmin()) {
        continue;
      }
      foreach ($values[$rid] as $srid => $role_grant) {
        $permission = 'RAC update ' . $srid;
        if ($role_grant && !$role->hasPermission($permission)) {
          $role->grantPermission($permission);
          $role->save();
        }
        elseif (!$role_grant && $role->hasPermission($permission)) {
          $role->revokePermission($permission);
          $role->save();
        }
      }
    }
    drupal_set_message($this->t('Role Access Relations have been Saved'));
  }

}

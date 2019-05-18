<?php

namespace Drupal\permission_matrix\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PermissionGroupForm.
 */
class PermissionGroupForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $permission_group = $this->entity;
    $permission_group_default = $this->getGroupPermission($this->entity);
    $config_permissions = $this->getSavedPermissions($this->entity);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $permission_group->label(),
      '#description' => $this->t("Label for the Permission group."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => time(),
      '#machine_name' => [
        'exists' => '\Drupal\permission_matrix\Entity\PermissionGroup::load',
      ],
      '#disabled' => !$permission_group->isNew(),
      '#access' => FALSE,
    ];

    $form['permissions'] = [
      '#title' => $this->t('Select Permissions to add in group'),
      '#type' => 'checkboxes',
      '#options' => $config_permissions,
      '#default_value' => $permission_group_default,
    ];

    if (empty($config_permissions)) {
      $form['noselection'] = [
        '#markup' => '<div>' . $this->t('No Permission available for selection') . '</div>', '#allowed_tags' => ['div']
      ];
    }

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $permissions = $form_state->getValue('permissions');
    $isOneSelected = FALSE;
    if (empty($permissions)) {
      $form_state->setErrorByName('permissions', $this->t('There is no permission to select. Please select permissions from the permission matrix config page.'));
    }
    else {
      foreach ($permissions as $key => $value) {
        if ($value !== 0) {
          $isOneSelected = TRUE;
        }

        if ($isOneSelected == FALSE) {
          $form_state->setErrorByName('permissions', $this->t('Please select at least one permissions.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $permission_group = $this->entity;
    $status = $permission_group->save();
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Permission group.', [
          '%label' => $permission_group->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Permission group.', [
          '%label' => $permission_group->label(),
        ]));
    }
    $form_state->setRedirectUrl($permission_group->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  private function getGroupPermission($entity) {
    $permissions = $entity->get('permissions');
    $perm = [];
    if ($permissions) {
      foreach ($permissions as $key => $val) {
        if ($val <> "") {
          $perm[] = $val;
        }
      }
    }
    return $perm;
  }

  /**
   * Get Permissions.
   */
  private function getSavedPermissions($entity) {
    $avoid = [];
    $current_group = 0;
    if (!$entity->isNew()) {
      $current_group = $entity->id();
    }

    // Get all permission groups.
    $entities = \Drupal::entityTypeManager()
      ->getStorage('permission_group')
      ->loadMultiple();
    foreach ($entities as $key => $val) {
      if ($val->id() != $current_group) {
        $entity_permissions = $val->get('permissions');
        foreach ($entity_permissions as $ek => $ev) {
          if ($ev <> "") {
            $avoid[] = $ev;
          }
        }
      }
    }

    $permission_config = $this->config('permission_matrix.config')->get('permission_matrix_config');
    $result = json_decode($permission_config);
    $permissions_array = [];
    foreach ($result as $val) {
      if (!in_array($val->permission, $avoid)) {
        $permissions_array[$val->permission] = $val->permission_label;
      }
    }
    return $permissions_array;
  }

}

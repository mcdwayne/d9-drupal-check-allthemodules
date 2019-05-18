<?php

namespace Drupal\licensing\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LicenseTypeForm.
 *
 * @package Drupal\licensing\Form
 */
class LicenseTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $license_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $license_type->label(),
      '#description' => $this->t("Label for the License type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $license_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\licensing\Entity\LicenseType::load',
      ],
      '#disabled' => !$license_type->isNew(),
    ];

    $target_entity_type = $license_type->get('target_entity_type');
    // @todo Make this more accurate. It might not have data.
    $has_data = (bool) $target_entity_type;
    $form['target_entity_type'] = array(
      '#type' => 'select',
      '#title' => t('Type of entity that can be licensed'),
      '#description' => t('Once you have selected an entity type, it cannot be changed!'),
      '#options' => \Drupal::entityManager()->getEntityTypeLabels(TRUE),
      '#default_value' => $license_type->get('target_entity_type'),
      '#required' => TRUE,
      // Disable if a license has already been created.
      '#disabled' => $has_data,
      '#size' => 1,
    );

    if ($has_data) {
      $bundles = \Drupal::entityManager()->getBundleInfo($target_entity_type);
      $options = [];
      foreach ($bundles as $bundle_machine_name => $values) {
        // The label does not need sanitizing since it is used as an optgroup
        // which is only supported by select elements and auto-escaped.
        $bundle_label = (string) $values['label'];
        $options[$bundle_machine_name] = $bundle_label;
      }

      $form['target_bundles'] = array(
        '#type' => 'checkboxes',
        '#title' => t('@target_entity_type bundles that can be licensed', ['@target_entity_type' => ucfirst($target_entity_type)]),
        '#description' => t('This value only affects new licenses. It will not change existing licenses.'),
        '#options' => $options,
        '#default_value' => $license_type->get('target_bundles') ? $license_type->get('target_bundles') : [],
        '#required' => TRUE,
        '#size' => 1,
      );
    }

    /** @var \Drupal\user\RoleInterface[] $roles */
    $roles = user_roles(TRUE);
    $role_options = [];
    foreach ($roles as $rid => $role) {
      $role_options[$rid] = $role->label();
    }
    $form['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Restricted roles'),
      '#description' => t('Roles whose entity access are restricted by license ownership. If no roles are selected, all roles will be restricted.'),
      '#options' => $role_options,
      '#default_value' => $license_type->get('roles') ? $license_type->get('roles') : array(),
    );

    $form['exempt_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Exempt roles'),
      '#description' => t('Users with these roles will NOT be restricted by license ownership, even if they also have a restricted role. Any user with the "Administer licenses" permission will also be exempt.'),
      '#options' => $role_options,
      '#default_value' => $license_type->get('exempt_roles') ? $license_type->get('exempt_roles') : array(),
      '#validate' => ['::validateExemptRoles'],
    );

    return $form;
  }

  /**
   *
   */
  public function validateExemptRoles(array $form, FormStateInterface $form_state) {
    $exempt_roles = $form_state->getValue('exempt_roles');
    $roles = $form_state->getValue('roles');
    $intersect = array_intersect($roles, $exempt_roles);
    if ($intersect) {
      $form_state->setErrorByName('exempt_roles', $this->t('You cannot define the same role as both restricted and exempt.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $license_type = $this->entity;
    $status = $license_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label License type.', [
          '%label' => $license_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label License type.', [
          '%label' => $license_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($license_type->urlInfo('collection'));
  }

}

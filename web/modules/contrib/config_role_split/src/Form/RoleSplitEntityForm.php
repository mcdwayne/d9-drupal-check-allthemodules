<?php

namespace Drupal\config_role_split\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Class RoleSplitEntityForm.
 *
 * @package Drupal\config_role_split\Form
 */
class RoleSplitEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $config = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $config->label(),
      '#description' => $this->t("Label for the Role Split."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $config->id(),
      '#machine_name' => [
        'exists' => '\Drupal\config_role_split\Entity\RoleSplitEntity::load',
      ],
    ];

    $form['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#description' => $this->t('The weight to order the filters.'),
      '#default_value' => $config->get('weight'),
    ];
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#description' => $this->t('Active filters get used by default, this property can be overwritten like any other config entity in settings.php.'),
      '#default_value' => ($config->get('status') ? TRUE : FALSE),
    ];

    $form['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter Mode'),
      // @TODO: improve filter.
      '#description' => $this->t('Select the mode of the filter.'),
      '#options' => [
        'split' => $this->t('Split'),
        'fork' => $this->t('Fork'),
        'exclude' => $this->t('Exclude'),
      ],
      '#multiple' => FALSE,
      '#default_value' => $config->get('mode'),
    ];

    $form['roles_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Roles'),
      '#description' => $this->t('Enter roles and permissions in yaml format.<br />This form element needs to be improved. See readme.'),
      '#size' => 5,
      '#default_value' => Yaml::encode($config->get('roles')),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    try {
      $roles = Yaml::decode($form_state->getValue('roles_text'));
      if (!is_array($roles)) {
        $roles = [];
      }
      foreach ($roles as $role => $permissions) {
        if (!is_array($permissions)) {
          $roles[$role] = [];
        }
      }

    }
    catch (\Exception $exception) {
      $roles = [];
    }

    $form_state->setValue('roles', $roles);

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $role_split = $this->entity;
    $status = $role_split->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Role Split.', [
          '%label' => $role_split->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Role Split.', [
          '%label' => $role_split->label(),
        ]));
    }
    $form_state->setRedirectUrl($role_split->toUrl('collection'));
  }

}

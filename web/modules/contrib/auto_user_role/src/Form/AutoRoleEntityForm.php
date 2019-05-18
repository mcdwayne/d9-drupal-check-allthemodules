<?php

namespace Drupal\auto_user_role\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AutoRoleEntityForm.
 *
 * @package Drupal\auto_user_role\Form
 */
class AutoRoleEntityForm extends EntityForm {

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Default roles that should not be used.
   *
   * @var array
   */
  protected $defaultRoles = ['anonymous', 'authenticated', 'administrator'];

  /**
   * AutoRoleEntityForm constructor.
   *
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   */
  public function __construct(RoleStorageInterface $role_storage, EntityFieldManagerInterface $entityFieldManager) {
    $this->roleStorage = $role_storage;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('entity.manager')->getStorage('user_role'),
        $container->get('entity_field.manager')
    );
  }

  /**
   * Gets the roles to display in this form.
   *
   * @return \Drupal\user\RoleInterface[]
   *   An array of role objects.
   */
  protected function getRoles() {
    return $this->roleStorage->loadMultiple();
  }

  /**
   * Get all user fields.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected function getUserFields() {
    $entity_type = 'user';
    $bundle = 'user';
    return $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $auto_role_entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $auto_role_entity->label(),
      '#description' => $this->t("Label for the Auto role."),
      '#required' => TRUE,
    ];

    $role_names = ["" => t('Select a role')];

    foreach ($this->getRoles() as $role_name => $role) {
      if (!in_array($role_name, $this->defaultRoles)) {
        // Retrieve role names for columns.
        $role_names[$role_name] = $role->label();
      }
    }

    $form['role'] = [
      '#type' => 'select',
      '#title' => $this->t('Role'),
      '#default_value' => $auto_role_entity->getRole(),
      '#description' => $this->t("Role for the Auto role."),
      '#required' => TRUE,
      '#options' => $role_names,
    ];

    $userFields = ["" => t('Select a field')];
    foreach ($this->getUserFields() as $field_name => $field) {
      if ($field instanceof FieldConfig) {
        $userFields[$field_name] = $field->label();
      }
    }

    $form['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#default_value' => $auto_role_entity->getField(),
      '#description' => $this->t("Field for the Auto role."),
      '#required' => TRUE,
      '#options' => $userFields,
    ];
    $form['field_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field value'),
      '#maxlength' => 255,
      '#default_value' => $auto_role_entity->getFieldValue(),
      '#description' => $this->t("Field value for the Auto role."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $auto_role_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\auto_user_role\Entity\AutoRoleEntity::load',
      ],
      '#disabled' => !$auto_role_entity->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $auto_role_entity = $this->entity;
    $status = $auto_role_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Auto role.', [
          '%label' => $auto_role_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Auto role.', [
          '%label' => $auto_role_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($auto_role_entity->toUrl('collection'));
  }

}

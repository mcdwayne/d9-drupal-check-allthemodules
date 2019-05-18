<?php

namespace Drupal\role_mixin\Hooks;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class UserRoleFormAlter {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $roleStorage;

  /**
   * Creates a new MixinRolePermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->roleStorage = $entity_type_manager->getStorage('user_role');
  }

  public function alter(&$form, FormStateInterface $form_state) {
    $options = array_map(function (EntityInterface $role) {
      return $role->label();
    }, $this->roleStorage->loadMultiple());
    /** @var \Drupal\user\RoleInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();
    $form['parent_roles'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $entity->getThirdPartySetting('role_mixin', 'parent_roles', []),
      '#title' => $this->t('Parent roles'),
    ];

    $form['#entity_builders'][] = static::class . '::submitRoleForm';
  }

  /**
   * Form submission handler for the user_role_form.
   */
  public static function submitRoleForm($entity_type, EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $parent_roles = array_filter($form_state->getValue('parent_roles'));
    /** @var \Drupal\user\RoleInterface $entity */
    $entity->setThirdPartySetting('role_mixin', 'parent_roles', $parent_roles);
  }

}

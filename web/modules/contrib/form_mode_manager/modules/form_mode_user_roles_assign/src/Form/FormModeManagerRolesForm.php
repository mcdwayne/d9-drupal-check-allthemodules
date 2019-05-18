<?php

namespace Drupal\form_mode_user_roles_assign\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\form_mode_manager\Form\FormModeManagerFormBase;
use Drupal\form_mode_manager\FormModeManagerInterface;
use Drupal\user\RoleInterface;

/**
 * Configure Form for Form Mode Manager Role Assign settings.
 */
class FormModeManagerRolesForm extends FormModeManagerFormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityDisplayRepositoryInterface $entity_display_repository, FormModeManagerInterface $form_mode_manager, CacheTagsInvalidatorInterface $cache_tags_invalidator, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory, $entity_display_repository, $form_mode_manager, $cache_tags_invalidator, $entity_type_manager);

    $this->ignoreExcluded = FALSE;
    $this->ignoreActiveDisplay = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_mode_manager_roles_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['form_mode_user_roles_assign.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableRoleOptions() {
    $roles = array_map(['\Drupal\Component\Utility\Html', 'escape'], user_role_names(TRUE));
    unset($roles[RoleInterface::AUTHENTICATED_ID]);

    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    if (isset($form['empty'])) {
      return $form;
    }

    $form['user_assign_form_mode_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Automatic Role assign per form mode'),
      '#open' => TRUE,
    ];

    $form['user_assign_form_mode_settings']['vertical_tabs_per_modes'] = [
      '#type' => 'vertical_tabs',
    ];

    $this->buildFormModeForm($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFormPerEntity(array &$form, array $form_modes, $entity_type_id) {
    if ('user' !== $entity_type_id) {
      return $this;
    }

    $entity_label = $this->entityTypeManager->getStorage($entity_type_id)->getEntityType()->getLabel();
    $form['user_assign_form_mode_settings'][$entity_type_id] = [
      '#type' => 'details',
      '#title' => $entity_label,
      '#description' => $this->t('Allows you to configure the user role automatically assigned for the entity <b>@entity_type_id</b> on registration form.', ['@entity_type_id' => $entity_label]),
      '#group' => 'vertical_tabs_per_modes',
    ];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFormPerFormMode(array &$form, array $form_mode, $entity_type_id) {
    if ('user' !== $entity_type_id) {
      return $this;
    }

    $form_mode_id = str_replace('.', '_', $form_mode['id']);
    $form['user_assign_form_mode_settings'][$entity_type_id]['form_modes'][$form_mode_id] = [
      '#type' => 'details',
      '#title' => $form_mode['label'],
      '#description' => $this->t('Assign role to (<b>@form_mode_id</b>) form registration.', ['@form_mode_id' => $form_mode['label']]),
      '#open' => TRUE,
    ];

    $form['user_assign_form_mode_settings'][$entity_type_id]['form_modes'][$form_mode_id]["{$form_mode_id}_roles"] = [
      '#title' => $this->t('Assign roles'),
      '#type' => 'select',
      '#empty_value' => [],
      '#empty_option' => $this->t('- Any -'),
      '#options' => $this->getAvailableRoleOptions(),
      '#default_value' => $this->settings->get("form_modes.{$form_mode_id}.assign_roles"),
      '#description' => $this->t("Select the type of theme you want to use. You can choose the themes defined by the drupal configuration (default or admin) or select one with the 'custom' option"),
      '#multiple' => TRUE,
    ];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettingsPerEntity(FormStateInterface $form_state, array $form_modes, $entity_type_id) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettingsPerFormMode(FormStateInterface $form_state, array $form_mode, $entity_type_id) {
    if ('user' !== $entity_type_id) {
      return $this;
    }

    $form_mode_id = str_replace('.', '_', $form_mode['id']);
    $this->settings
      ->set("form_modes.{$form_mode_id}.assign_roles", $form_state->getValue("{$form_mode_id}_roles"));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->cacheTagsInvalidator->invalidateTags([
      'roles',
      'rendered',
    ]);
  }

}

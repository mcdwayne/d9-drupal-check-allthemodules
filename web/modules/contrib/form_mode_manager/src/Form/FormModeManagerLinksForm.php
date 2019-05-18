<?php

namespace Drupal\form_mode_manager\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\form_mode_manager\FormModeManagerInterface;

/**
 * Configure Form Mode Manager links.
 */
class FormModeManagerLinksForm extends FormModeManagerFormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityDisplayRepositoryInterface $entity_display_repository, FormModeManagerInterface $form_mode_manager, CacheTagsInvalidatorInterface $cache_tags_invalidator, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory, $entity_display_repository, $form_mode_manager, $cache_tags_invalidator, $entity_type_manager);

    $this->ignoreExcluded = FALSE;
    $this->ignoreActiveDisplay = FALSE;
  }

  /**
   * Available positioning of generated Local tasks.
   *
   * @var array
   */
  protected $localTaskTypes = [
    'primary' => 'Primary tasks',
    'secondary' => 'Secondary tasks',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_mode_manager_links_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['form_mode_manager.links'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    if (isset($form['empty'])) {
      return $form;
    }

    $form['local_taks'] = [
      '#type' => 'details',
      '#title' => $this->t('Local Tasks'),
      '#open' => TRUE,
    ];

    $form['local_taks']['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
    ];

    $this->buildFormModeForm($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFormPerEntity(array &$form, array $form_modes, $entity_type_id) {
    $entity_label = $this->entityTypeManager->getStorage($entity_type_id)->getEntityType()->getLabel();
    $form['local_taks']["{$entity_type_id}_local_taks"] = [
      '#type' => 'details',
      '#title' => $entity_label,
      '#description' => $this->t('The following options are available for make a better flexibility of local task displaying.'),
      '#group' => 'vertical_tabs',
    ];

    $form['local_taks']["{$entity_type_id}_local_taks"]['tasks_location_' . $entity_type_id] = [
      '#title' => $this->t('Position of Local tasks'),
      '#type' => 'select',
      '#options' => $this->localTaskTypes,
      '#default_value' => $this->settings->get("local_tasks.{$entity_type_id}.position"),
      '#description' => $this->t('The location of local tasks. <ul><li><b>Primary level</b> are at the same position as "Edit" default task</li><li><b>Secondary</b> level place all form-modes tasks below "Edit" task (at secondary menu). </li></ul>'),
      '#weight' => 0,
    ];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFormPerFormMode(array &$form, array $form_mode, $entity_type_id) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettingsPerEntity(FormStateInterface $form_state, array $form_modes, $entity_type_id) {
    $this->settings->set("local_tasks.{$entity_type_id}.position", $form_state->getValue('tasks_location_' . $entity_type_id));
  }

  /**
   * {@inheritdoc}
   */
  public function setSettingsPerFormMode(FormStateInterface $form_state, array $form_mode, $entity_type_id) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->cacheTagsInvalidator->invalidateTags([
      'local_task',
      'rendered',
    ]);
  }

}

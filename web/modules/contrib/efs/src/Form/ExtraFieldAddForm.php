<?php

namespace Drupal\efs\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\efs\Entity\ExtraField;

/**
 * Provides a form for adding a fieldgroup to a bundle.
 */
class ExtraFieldAddForm extends FormBase {

  /**
   * The prefix for groups.
   *
   * @var string
   */
  const GROUP_PREFIX = 'group_';

  /**
   * The name of the entity type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The context for the group.
   *
   * @var string
   */
  protected $context;

  /**
   * The mode for the group.
   *
   * @var string
   */
  protected $mode;

  /**
   * Current step of the form.
   *
   * @var string
   */
  protected $currentStep;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'extra_field_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL, $context = NULL) {

    if ($context == 'form') {
      $this->mode = \Drupal::request()->get('form_mode_name');
    }
    else {
      $this->mode = \Drupal::request()->get('view_mode_name');
      $context = 'display';
    }

    if (empty($this->mode)) {
      $this->mode = 'default';
    }

    if (!$form_state->get('context')) {
      $form_state->set('context', $context);
    }
    if (!$form_state->get('entity_type_id')) {
      $form_state->set('entity_type_id', $entity_type_id);
    }
    if (!$form_state->get('bundle')) {
      $form_state->set('bundle', $bundle);
    }
    if (!$form_state->get('step')) {
      $form_state->set('step', 'formatter');
    }

    $this->entityTypeId = $form_state->get('entity_type_id');
    $this->bundle = $form_state->get('bundle');
    $this->context = $form_state->get('context');
    $this->currentStep = $form_state->get('step');

    $form['entity_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity type'),
      '#maxlength' => 255,
      '#default_value' => $this->entityTypeId,
      '#required' => TRUE,
      '#access' => FALSE,
    ];

    $form['bundle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bundle'),
      '#maxlength' => 255,
      '#default_value' => $this->bundle,
      '#required' => TRUE,
      '#access' => FALSE,
    ];

    $form['context'] = [
      '#type' => 'select',
      '#title' => $this->t('Context'),
      '#options' => ['display' => 'Display', 'form' => 'Form'],
      '#default_value' => $this->context,
      '#required' => TRUE,
      '#access' => FALSE,
    ];

    $form['mode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mode'),
      '#maxlength' => 255,
      '#default_value' => $this->mode,
      '#required' => TRUE,
      '#access' => FALSE,
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => '',
      '#description' => $this->t("Label for the Extra field."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => '',
      '#machine_name' => [
        'exists' => '\Drupal\efs\Entity\ExtraField::load',
      ],
      '#disabled' => FALSE,
    ];

    $manager = \Drupal::service('plugin.manager.efs.formatters');
    $plugins = [];
    $definitions = $manager->getDefinitions();
    foreach ($definitions as $id => $def) {
      if (!in_array($context, $def['supported_contexts'])) {
        continue;
      }
      $instance = $manager->createInstance($id);
      if ($instance->isApplicable($this->entityTypeId, $this->bundle)) {
        $plugins[$id] = $def['label'];
      }
    }
    $form['plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Plugin'),
      '#options' => $plugins,
      // '#default_value' => $extra_field->getPlugin(),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create field'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $values = $form_state->getValues();
    $values['field_name'] = $values['id'];
    $id = [
      $values['entity_type'],
      $values['bundle'],
      $values['context'],
      $values['mode'],
      $values['id'],
    ];
    $values['id'] = implode('.', $id);

    $extra_field = ExtraField::create($values);
    $extra_field->save();

    drupal_set_message(t('New field %label successfully created.', ['%label' => $values['label']]));

    $form_state->setRedirectUrl($this->getFieldUiRoute());
    Cache::invalidateTags(['entity_field_info']);
  }

  /**
   * Get the field ui route that should be used for given arguments.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getFieldUiRoute() {

    $entity_type = \Drupal::entityTypeManager()
      ->getDefinition($this->entityTypeId);
    if ($entity_type->get('field_ui_base_route')) {

      $context_route_name = "";
      $mode_route_name = "default";
      $route_parameters = self::getRouteBundleParameter($entity_type, $this->bundle);

      // Get correct route name based on context and mode.
      if ($this->context == 'form') {
        $context_route_name = 'entity_form_display';

        if ($this->mode != 'default') {
          $mode_route_name = 'form_mode';
          $route_parameters['form_mode_name'] = $this->mode;
        }

      }
      else {
        $context_route_name = 'entity_view_display';

        if ($this->mode != 'default') {
          $mode_route_name = 'view_mode';
          $route_parameters['view_mode_name'] = $this->mode;
        }

      }

      return new Url("entity.{$context_route_name}.{$this->entityTypeId}.{$mode_route_name}", $route_parameters);
    }
  }

  /**
   * Gets the route parameter that should be used for Field UI routes.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The actual entity type, not the bundle (e.g. the content entity type).
   * @param string $bundle
   *   The bundle name.
   *
   * @return array
   *   An array that can be used a route parameter.
   */
  public static function getRouteBundleParameter(EntityTypeInterface $entity_type, $bundle) {
    $bundle_parameter_key = $entity_type->getBundleEntityType() ?: 'bundle';
    return [$bundle_parameter_key => $bundle];
  }

}

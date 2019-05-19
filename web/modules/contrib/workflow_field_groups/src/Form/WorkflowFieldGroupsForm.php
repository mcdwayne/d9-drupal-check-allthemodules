<?php

namespace Drupal\workflow_field_groups\Form;

use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Field presets creation form.
 */
class WorkflowFieldGroupsForm extends FormBase {

  /**
   * Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(Request $request, EntityTypeManagerInterface $entity_manager, EntityFormBuilderInterface $form_builder) {
    $this->request = $request;
    $this->entityTypeManager = $entity_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('entity.manager'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_field_groups_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    $form_mode_name = $this->request->attributes->get('form_mode_name');
    $form_operation = $this->request->attributes->get('form_operation');

    $bundle = $this->request->attributes->get('bundle');
    $bundle_entity_type = $this->request->attributes->get('bundle_entity_type');

    $groups = field_group_info_groups($entity_type_id, $bundle, 'form', $form_mode_name);

    if (count($groups) === 0) {
      drupal_set_message($this->t('No field groups found on this form display.'), 'error');

      return $form;
    }

    $form['bundle_entity_type'] = [
      '#type' => 'hidden',
      '#value' => $bundle_entity_type,
    ];

    $form['form_mode_name'] = [
      '#type' => 'hidden',
      '#value' => $form_mode_name,
    ];

    $form['form_operation'] = [
      '#type' => 'hidden',
      '#value' => $form_operation,
    ];

    $form['bundle'] = [
      '#type' => 'hidden',
      '#value' => $bundle,
    ];

    $form['entity_type_id'] = [
      '#type' => 'hidden',
      '#value' => $entity_type_id,
    ];

    $bundle_entity = $this->entityTypeManager->getStorage($bundle_entity_type)->load($bundle);
    $workflow_id = $bundle_entity->getThirdPartySetting('workflow_field_groups', 'workflow');
    $workflow_states = workflow_get_workflow_state_names($workflow_id);

    $header = [$this->t('Field Group'), $this->t('(Creation)')];
    foreach ($workflow_states as $workflow_state) {
      $header[] = $workflow_state;
    }

    $form["title_$form_operation"] = [
      '#type' => 'item',
      '#title' => t('Field group @form_operation access by workflow state by role', ['@form_operation' => $form_operation]),
      '#description' => t('Set checkboxes to specify which roles can @form_operation field groups at different workflow states.', ['@form_operation' => $form_operation]),
    ];

    $form["settings_$form_operation"] = [
      '#type' => 'table',
      '#header' => $header,
    ];

    $form_display = $this->entityTypeManager->getStorage('entity_form_display')->load($entity_type_id . '.' . $bundle . '.' . $form_mode_name);

    $settings = $form_display->getThirdPartySetting('workflow_field_groups', $form_mode_name, NULL);

    foreach ($groups as $group_id => $group) {
      $form['settings_' . $form_operation][$group_id]['group'] = [
        '#markup' => $group->label . ' [' . $group->group_name . ']',
      ];

      $options = user_role_names();
      $workflow_states = [$workflow_id . '_' . WORKFLOW_CREATION_STATE_NAME => '_creation_'] + $workflow_states;

      foreach ($workflow_states as $workflow_state_id => $workflow_state) {

        $default_value = [];
        if (isset($settings[$form_operation][$group_id][$workflow_state_id])) {
          $default_value = $settings[$form_operation][$group_id][$workflow_state_id];
        }

        $form['settings_' . $form_operation][$group_id][$workflow_state_id] = [
          '#type' => 'checkboxes',
          '#options' => user_role_names(TRUE),
          '#default_value' => $default_value,
        ];
      }
    };

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $form_operation = $form_state->getValue('form_operation');
    $form_mode_name = $form_state->getValue('form_mode_name');

    $form_display = $this->entityTypeManager->getStorage('entity_form_display')->load($values['entity_type_id'] . '.' . $values['bundle'] . '.' . $values['form_mode_name']);

    $previous_values = $form_display->getThirdPartySetting('workflow_field_groups', $form_mode_name);
    $previous_values[$form_operation] = $values['settings_' . $form_operation];

    $form_display->setThirdPartySetting('workflow_field_groups', $values['form_mode_name'], $previous_values)->save();

    drupal_set_message($this->t('Settings have been saved.'));
  }

}

<?php

namespace Drupal\stacks\WidgetAdmin\Form;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\stacks\WidgetAdmin\Manager\StepManager;
use Drupal\stacks\WidgetAdmin\Step\StepsEnum;
use Drupal\inline_entity_form\ElementSubmit;
use Drupal\inline_entity_form\WidgetSubmit;
use Drupal\stacks\Entity\WidgetInstanceEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\stacks\WidgetAdmin\Button\StepCancelButton;
use Drupal\stacks\WidgetAdmin\Validator\ValidatorRequired;

/**
 * Class WidgetForm
 * @package Drupal\stacks\Form\widgetform
 */
class WidgetFormAdmin extends FormBase {

  protected $step_id;
  protected $step;
  protected $stepManager;

  public function __construct() {
    $this->step_id = StepsEnum::STEP_ONE;
    $this->stepManager = new StepManager();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stacks';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attached']['library'][] = 'stacks/admin_widget_forms';

    // Get step from step manager.
    $this->step = $this->stepManager->getStep($this->step_id);
    $this->step_id = $this->step->setStep();
    $form['step_id'] = ['#type' => 'hidden', '#value' => $this->step_id];

    $form['wrapper-messages'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'messages-wrapper',
      ],
    ];

    $delta = $_GET['delta'];
    $wrapper = "wrapper-{$delta}";

    $form[$wrapper] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => "form-wrapper-{$delta}",
        'class' => ['form-wrapper'],
      ],
    ];

    if ($this->step_id == 2) {
      ElementSubmit::attach($form, $form_state);
    }

    // Attach step form elements.
    $form[$wrapper] += $this->step->buildStepFormElements();

    // Attach buttons.
    $form[$wrapper]['actions']['#type'] = 'actions';

    $cancelButton = new StepCancelButton();
    $form[$wrapper]['actions'][$cancelButton->getKey()] = $cancelButton->build();

    $buttons = $this->step->getButtons();
    foreach ($buttons as $button) {
      /** @var \Drupal\stacks\WidgetAdmin\Button\ButtonInterface $button */
      $form[$wrapper]['actions'][$button->getKey()] = $button->build();

      if ($button->ajaxify()) {
        // Add ajax to button.
        $form[$wrapper]['actions'][$button->getKey()]['#ajax'] = [
          'callback' => [get_class($this), 'loadStep'],
          'wrapper' => "form-wrapper-{$delta}",
          'effect' => 'fade',
        ];
      }

      $callable = [$this, $button->getSubmitHandler()];
      if ($button->getSubmitHandler() && is_callable($callable)) {
        // attach submit handler to button, so we can execute it later on..
        $form[$wrapper]['actions'][$button->getKey()]['#submit_handler'] = $button->getSubmitHandler();
      }

      if ($button->getKey() == 'finish') {
        // As we are doing our own submission handling, we
        // do what just what we need for IEF to work
        // on our button.
        // @see ElementSubmit::addCallback
        $element =& $form[$wrapper]['actions'][$button->getKey()];
        $element['#ief_submit_trigger']  = TRUE;
        $element['#ief_submit_trigger_all'] = TRUE;
      }
    }

    return $form;
  }

  /**
   * Ajax callback to load new step.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  static public function loadStep(array &$form, FormStateInterface $form_state) {

    $response = new AjaxResponse();

    $messages = drupal_get_messages();
    if (!empty($messages)) {
      // Form did not validate, get messages and render them.
      $messages = [
        '#theme' => 'status_messages',
        '#message_list' => $messages,
        '#status_headings' => [
          'status' => t('Status message'),
          'error' => t('Error message'),
          'warning' => t('Warning message'),
        ],
      ];

      $response->addCommand(new HtmlCommand('#messages-wrapper', $messages));
    }
    else {
      // Remove messages.
      $response->addCommand(new HtmlCommand('#messages-wrapper', ''));
    }

    $delta = $_GET['delta'];

    // Update Form.
    $response->addCommand(new HtmlCommand("#form-wrapper-{$delta}", $form["wrapper-{$delta}"]));

    // Removing step 1 actions
    if ($form['step_id']['#value'] == 2) {
      $response->addCommand(new RemoveCommand('#form-wrapper-'.$delta.' + div[data-drupal-selector="edit-actions"]'));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    // Only validate if validation doesn't have to be skipped.
    // For example on "previous" button.
    if (empty($triggering_element['#skip_validation']) && $fields_validators = $this->step->getFieldsValidators()) {

      // Validate fields.
      foreach ($fields_validators as $field => $validators) {

        // Validate all validators for this field.
        foreach ($validators as $validator) {

          // Trigger the validate() method on the validation class.
          $validator->setFormState($form_state);
          if (!$validator->validates($form_state->getValue($field))) {
            $form_state->setErrorByName($field, $validator->getErrorMessage());
          }

        } // End $validators foreach loop.

      } // End $fields_validators foreach loop.
    } // End #skip_validation if check.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set step to navigate to.
    $triggering_element = $form_state->getTriggeringElement();
    $go_to_step = $triggering_element['#goto_step'];
    $values = [];

    // Save the values. Do not save the values if the previous button was clicked.
    // We do this to prevent wiping out the stacks entity from step #2.
    if (!isset($triggering_element['#previous'])) {

      // Save filled out values to step. So we can use them as default_value later on.
      foreach ($this->step->getFieldNames() as $name) {
        $values[$name] = $form_state->getValue($name);
      }

      // If the widget_template value is here, let's process it so we can add the
      // bundle and template values.
      if ($form_state->getValue('widget_template')) {
        $template_info = $this->stepManager->extractBundleFromTemplate($form_state->getValue('widget_template'));
        $values['bundle'] = $template_info['bundle'];
        $values['template'] = $template_info['template'];
      }

      $this->step->setValues($values);
      $this->stepManager->addStep($this->step);
    }

    // If an extra submit handler is set, execute it.
    if (isset($triggering_element['#submit_handler'])){
      $this->{$triggering_element['#submit_handler']}($form, $form_state);
    }

    // Setting reusable field
    if ($form_state->getValue('reusable')) {
      $values['reusable'] = $form_state->getValue('reusable');
    }

    // Set the next step to load.
    $this->step_id = $go_to_step;

    // Rebuild the form with the correct step.
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit handler for last step of form.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitValues(array &$form, FormStateInterface $form_state) {
    // Handle Inline Entity Form...create Widget Entity
    ElementSubmit::trigger($form, $form_state);

    $delta = $_GET['delta'];
    $wrapper = "wrapper-{$delta}";
    $widget_entity = $form[$wrapper]['inline_entity_form']['#entity'];

    $widget_id = $widget_entity->id();

    // Get values and extract the template value.
    $steps = $this->stepManager->getAllSteps();
    $step1 = $steps[1];
    $step1_values = $steps[1]->getValues();
    $step2_values = $steps[2]->getValues();
    $template_info = $this->stepManager->extractBundleFromTemplate($step1_values['widget_template']);

    // Mark widget sharing depending on Stacks' configuration
    if ($form_state->getValue('reusable') == 1) {
      $step1_values['reusable'] = 1;
    } else {
      $step1_values['reusable'] = 0;
    }

    if (empty($step1_values['widget_instance_id'])) {
      // Create Widget Instance
      $widget_instance_entity = WidgetInstanceEntity::create([
        'type' => 'widget_instance_entity',
        'title' => $step1_values['widget_name'],
        'template' => $template_info['template'],
        'theme' => $step1_values['widget_theme'],
        'status' => $step1_values['status'],
        'enable_sharing' => $step1_values['reusable'],
        'widget_entity' => $widget_id,
        'wrapper_id' => $step2_values['wrapper_id'],
        'wrapper_classes' => $step2_values['wrapper_classes'],
      ]);
    }
    else {
      // Update Widget Instance
      $widget_instance_entity = WidgetInstanceEntity::load($step1_values['widget_instance_id']);

      // $widget_instance_entity->setTitle($step1_values['widget_name']);
      $widget_instance_entity->setTitle($form_state->getValue('widget_name'));

      $widget_instance_entity->setTemplate($template_info['template']);
      $widget_instance_entity->setTheme($step1_values['widget_theme']);
      $widget_instance_entity->setStatus($step1_values['status']);
      $widget_instance_entity->setWidgetEntityID($widget_id);
      $widget_instance_entity->setWrapperID($step2_values['wrapper_id']);
      $widget_instance_entity->setWrapperClasses($step2_values['wrapper_classes']);

      $widget_instance_entity->setEnableSharing($step1_values['reusable']);
    }

    // Enabling widget instance title replacement in step 2.
    if ($this->step_id != 1) {
      $widget_instance_entity->setTitle($form_state->getValue('widget_name'));
    }

    $widget_instance_entity->save();
    $widget_instance_id = $widget_instance_entity->id();

    // Add widget instance id to the values in step #1.
    $step1_values['widget_instance_id'] = $widget_instance_id;
    $step1->setValues($step1_values);

    // Success message is display in StepFinalize
  }

  /**
   * Submit handler for last step of form with existing widgets
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitValuesExisting(array &$form, FormStateInterface $form_state) {

    $selected_value = $form_state->getValue('existing_stacks_table');
    if($selected_value) {
/*
      $widget_instance_entity = WidgetInstanceEntity::load($selected_value);
      $widget_id = $widget_instance_entity->getWidgetEntityID();
*/
      // Get values and extract the template value.
      $steps = $this->stepManager->getAllSteps();
      $step1 = $steps[1];
      $step1_values = $steps[1]->getValues();
  //    $step2_values = $steps[2]->getValues();
/*
      $step1_values['widget_name'] = $widget_instance_entity->getTitle() . " (Copy)";
      $template_info['template'] = $widget_instance_entity->getTemplate();
      $step1_values['widget_theme'] = $widget_instance_entity->getTheme();
      $step2_values['wrapper_id'] = $widget_instance_entity->getWrapperID();
      $step2_values['wrapper_classes'] = $widget_instance_entity->getWrapperClasses();
*/
      // Add widget instance id to the values in step #1.
      $step1_values['widget_instance_id'] = $selected_value;
      $step1->setValues($step1_values);
    }

  }

}

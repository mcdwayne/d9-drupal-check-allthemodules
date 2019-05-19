<?php

namespace Drupal\state_form_entity\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\state_form_entity\StateFormEntityHelpers;

/**
 * Class StateFormEntityForm
 * @package Drupal\state_form_entity\Form
 */
class StateFormEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\state_form_entity\Entity\StateFormEntity $state */
    $state = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add state');
    }
    else {
      $form['#title'] = $this->t('Edit %label state', ['%label' => $state->label()]);
    }

    $form['name'] = [
      '#title' => t('State name'),
      '#type' => 'textfield',
      '#default_value' => $state->getName(),
      '#description' => t('The human-readable name of this content type. This text will be displayed as part of the list on the <em>Add content</em> page. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $state_prefix = $this->config('state_form_entity.settings')->get('state_prefix');
    $form['type'] = [
      '#type' => 'machine_name',
      '#field_prefix' => '<span dir="ltr">' . $state_prefix,
      '#field_suffix' => '</span>&lrm;',
      '#default_value' => $state->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => FALSE,
      '#machine_name' => [
        'exists' => ['Drupal\node\Entity\NodeType', 'load'],
        'source' => ['name'],
      ],
      '#description' => t('A unique machine-readable name for this content type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %node-add page, in which underscores will be converted into hyphens.', [
        '%node-add' => t('Add content'),
      ]),
    ];

    $form['formFieldParent'] = [
      '#title' => t('Entity Type'),
      '#type' => 'select',
      '#options' => $this->displayListEntityAvailable(),
      '#default_value' => $state->getFormFieldParent(),
      '#description' => t('Select what kind of entity has field target and toggle.'),
    ];

    $options = self::generateFieldsOption();
    $form['fieldTarget'] = [
      '#title' => t('Field target'),
      '#type' => 'select',
      '#options' => $options,
      '#multiple' => TRUE,
      '#default_value' => $state->getFieldTarget(),
      '#description' => t('Select the field target by effect.'),
      '#prefix' => "<div id='ajax-wrapper-entity-field-target-select'>",
      '#suffix' => '</div>',
    ];

    $form['fieldToggle'] = [
      '#title' => t('Field toggle'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $state->getFieldToggle(),
      '#description' => t('Select the field active effect on field target.'),
      '#prefix' => "<div id='ajax-wrapper-entity-field-toggle-select'>",
      '#suffix' => '</div>',
    ];

    $form['statesFormEntityTypeElement'] = [
      '#title' => $this->t('State type element'),
      '#type' => 'select',
      '#options' => [
        'STATE_ELEMENTS' => $this->t('State elements'),
        'STATE_REMOTE' => $this->t('State remote'),
        'STATE_PROBABLY_USELESS' => $this->t('State probably useless'),
      ],
      '#default_value' => $state->getStateFormEntityTypeElement(),
      '#description' => $this->t('This select allow user to select the state type.'),
    ];

    $form['statesFormEntityType'] = [
      '#title' => t('State type'),
      '#type' => 'select',
      '#options' => self::getDefaultHelpers(),
      '#default_value' => $state->getStateFormEntityType(),
      '#description' => $this->t('This select allow user to select a behaviour.'),
      '#prefix' => "<div id='ajax-wrapper-entity-states-type-select'>",
      '#suffix' => '</div>',
    ];

    $form['valueNested'] = [
      '#title' => t('Field value'),
      '#type' => 'textfield',
      '#default_value' => $state->getValueNested(),
      '#description' => $this->t('Define the value waiting to active effect.'),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#default_value' => $state->getDescription(),
      '#description' => t('Allow user to set a little describe of behavior.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save state');
    $actions['delete']['#value'] = $this->t('Delete state');
    return $actions;
  }

  /**
   * @return array
   */
  public static function displayListEntityAvailable() {
    $options = ['_none' => '- Aucun(e) -'];
    $entityList = \Drupal::service('entity_type.repository')->getEntityTypeLabels();

    foreach ($entityList as $key => $item) {
      if (\Drupal::entityTypeManager()->getDefinition($key)->entityClassImplements(FieldableEntityInterface::class)) {
        $options[$key] = $item;
      }
    }

    return $options;
  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public static function displayListFieldAvailable(&$form,FormStateInterface $form_state) {
    $type = $form_state->getValue('formFieldParent');
    $response = new AjaxResponse();
    $options = ['_none' => '- Aucun(e) -'];
    $fields = \Drupal::service('entity_field.manager')->getFieldMap();

    $fieldsList = array_keys($fields[$type]);

    foreach ($fieldsList as $field) {
      $options[$field] = $field;
    }

    $form['fieldTarget']['#options'] = $options;
    $response->addCommand(new ReplaceCommand('#ajax-wrapper-entity-field-target-select', $form['fieldTarget']));
    return $response;
  }

  /**
   * @return array
   */
  public static function generateFieldsOption() {
    $fields = \Drupal::service('entity_field.manager')->getFieldMap();
    $options = ['_none' => '- Aucun(e) -'];
    $entityList = self::displayListEntityAvailable();
    foreach ($entityList as $key => $type) {
      $optionsFields = array_keys($fields[$key]);
      foreach ($optionsFields as $field) {
        $options[$field] = $field;
      }
    }

    return $options;
  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function generateFieldsListOption(&$form,FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $type = $form_state->getValue('formFieldParent');
    $fields = \Drupal::service('entity_field.manager')->getFieldMap();
    $options = ['_none' => '- Aucun(e) -'];

    $fieldsList = $form_state->getValue('fieldTarget');

    $optionsFields = array_keys($fields[$type]);

    foreach ($optionsFields as $field) {
      $options[$field] = $field;
    }
    unset($options[$fieldsList]);

    $form['fieldToggle']['#options'] = $options;
    $response->addCommand(new ReplaceCommand('#ajax-wrapper-entity-field-toggle-select', $form['fieldToggle']));
    return $response;
  }

  /**
   * @return mixed
   */
  public static function getDefaultHelpers() {
    $options = StateFormEntityHelpers::getStateList('STATE_ELEMENTS');
    return $options;
  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public static function getHelpers(&$form,FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $selected = $form_state->getValue('stateFormEntityTypeElement');
    $options = StateFormEntityHelpers::getStateList($selected);

    $form['statesType']['#options'] = $options;
    $response->addCommand(new ReplaceCommand('#ajax-wrapper-entity-states-type-select', $form['statesType']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\state_form_entity\Entity\StateFormEntity $state */
    $state = $this->entity;

    $status = $state->save();

    if ($status) {
      // Setting the success message.
      drupal_set_message($this->t('Saved the state: @name.', array(
        '@name' => $state->getName()
      )));
    }
    else {
      drupal_set_message($this->t('The @name state was not saved.', array(
        '@name' => $state->getName(),
      )));
    }

    $form_state->setRedirect('entity.state_form_entity.collection');
  }

}

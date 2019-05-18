<?php

namespace Drupal\formazing\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\formazing\Entity\FieldFormazingEntity;
use Drupal\formazing\Entity\FormazingEntity;
use Drupal\formazing\FieldSettings\CheckboxesField;
use Drupal\formazing\FieldSettings\CheckboxField;
use Drupal\formazing\FieldSettings\DateField;
use Drupal\formazing\FieldSettings\RadiosField;
use Drupal\formazing\FieldSettings\SelectField;
use Drupal\formazing\FieldSettings\SubmitField;
use Drupal\formazing\FieldSettings\TextareaField;
use Drupal\formazing\FieldSettings\TextField;

/**
 * Class AddFieldForm.
 */
class AddFieldForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_field_form';
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param FormazingEntity $formazing_entity
   * @return array
   */
  public function buildForm(
    array $form, FormStateInterface $form_state, $formazing_entity = NULL
  ) {
    $form_state->set('formazing_id', $formazing_entity);

    $config = $this->config('formazing.addfield');
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('label'),
      '#required' => TRUE,
    ];
    $form['type_of_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of field'),
      '#options' => [
        CheckboxField::class => t('Checkbox', [], ['context' => 'formazing']),
        CheckboxesField::class => t('Checkboxes', [], ['context' => 'formazing']),
        DateField::class => t('Date', [], ['context' => 'formazing']),
        SelectField::class => t('Select', [], ['context' => 'formazing']),
        SubmitField::class => t('Submit', [], ['context' => 'formazing']),
        RadiosField::class => t('Radios', [], ['context' => 'formazing']),
        TextareaField::class => t('Textarea', [], ['context' => 'formazing']),
        TextField::class => t('Textfield', [], ['context' => 'formazing']),

      ],
      '#size' => 1,
      '#default_value' => $config->get('type_of_field'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\formazing\Entity\FieldFormazingEntity $value */
    $type = $form_state->getValue('type_of_field');

    $formazingId = $form_state->get('formazing_id');

    $entity = FieldFormazingEntity::create([
      'name' => $form_state->getValue('label'),
      'status' => 1,
      'formazing_id' => (int) $formazingId,
      'field_type' => $type,
      'weight' => 0,
    ]);

    $entity->save();

    return $form_state->setRedirect('entity.formazing_entity_field.edit', [
      'formazing_entity' => $formazingId,
      'field_formazing_entity' => $entity->id()
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'formazing.addfield',
    ];
  }
}

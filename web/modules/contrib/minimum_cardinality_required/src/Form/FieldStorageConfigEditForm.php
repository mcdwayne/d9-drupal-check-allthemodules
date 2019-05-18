<?php

namespace Drupal\minimum_cardinality_required\Form;

use Drupal\field_ui\Form;
use Drupal\field_ui\Form\FieldStorageConfigEditForm as BaseFieldStorageConfigEditForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Extend the FieldStorageConfigEditForm.
 *
 * Class for Adding unlimited_with_requirement option.
 */
class FieldStorageConfigEditForm extends BaseFieldStorageConfigEditForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    if ($form_state->get('field_config')->getType() == 'image') {
      return $form;
    }
    $field_instance_id = $form_state->get('field_config')->getOriginalId();
    $config = \Drupal::config('field.field.' . $field_instance_id);
    $cardinality_number_fake = $config->get('cardinality_number_fake');
    $cardinality = $this->entity->getCardinality();
    if ($cardinality_number_fake < -1) {
      $default_value = 'unlimited_with_requirement';
    }
    else {
      $default_value = ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) ? FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED : 'number';
    }
    $form['cardinality_container']['cardinality'] = [
      '#type' => 'select',
      '#title' => $this->t('Allowed number of values'),
      '#title_display' => 'invisible',
      '#options' => [
        'number' => $this->t('Limited'),
        FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED => $this->t('Unlimited'),
        'unlimited_with_requirement' => $this->t('Unlimited With Minimum'),
      ],
      '#default_value' => $default_value,
    ];
    $form['cardinality_container']['cardinality_number_fake'] = [
      '#type' => 'number',
      '#default_value' => $cardinality_number_fake,
      '#title' => $this->t('Limit'),
      '#title_display' => 'invisible',
      '#min' => -99,
      '#size' => 2,
      '#states' => [
        'visible' => [
          ':input[name="cardinality"]' => ['value' => 'unlimited_with_requirement'],
        ],
        'disabled' => [
          ':input[name="cardinality"]' => ['value' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED],
        ],
      ],
    ];
    return $form;
  }

  /**
   * Add custom validation to validate cardinality.
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('cardinality') === 'number' && $form_state->getValue('cardinality_number') < 0) {
      $form_state->setErrorByName('cardinality_number', $this->t('Number should not negtaive.'));
    }
    if ($form_state->getValue('cardinality') === 'unlimited_with_requirement' && $form_state->getValue('cardinality_number_fake') >= -1) {
      $form_state->setErrorByName('cardinality_number', $this->t('Number should not positive or not equal to -1.'));
    }
    if ($form_state->getValue('cardinality') === 'unlimited_with_requirement' && !$form_state->getValue('cardinality_number_fake')) {
      $form_state->setErrorByName('cardinality_number', $this->t('Number should not positive.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    self::validate($form, $form_state);
    $field_instance_id = $form_state->get('field_config')->getOriginalId();
    $config = \Drupal::service('config.factory')->getEditable('field.field.' . $field_instance_id);
    if ($form_state->getValue('cardinality') === 'unlimited_with_requirement' && $form_state->getValue('submit') !== NULL) {
      $form_state->setValue('cardinality', FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
      $config->set('cardinality_number_fake', $form_state->getValue('cardinality_number_fake'));
    }
    elseif ($form_state->getValue('cardinality') != 'unlimited_with_requirement' && $form_state->getValue('submit') !== NULL) {
      $config->set('cardinality_number_fake', 0);
    }
    $config->save();
    return parent::buildEntity($form, $form_state);
  }

}

<?php

namespace Drupal\entity_title_length\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TitleLengthForm.
 *
 * @package Drupal\entity_title_length\Form
 */
class TitleLengthForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_title_length_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'entity_title_length.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['entity_name'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Entity name'),
      '#required' => TRUE,
      '#options' => [
        'node' => 'node',
      ],
      "#default_value" => [
        'node',
      ],
      '#weight' => 0,
    ];

    $form['title_length'] = [
      '#type' => 'number',
      '#title' => 'Text length',
      '#description' => $this->t('Set the length that you prefer.'),
      '#default_value' => $this->config('entity_title_length.config')
        ->get('title_length'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Validate form input.
    $values = $form_state->getValues();
    if (!is_numeric($values['title_length'])) {
      $form_state->setErrorByName('title_length', $this->t('This should be a number!'));
    }
    // Check if the number is between 1 and 65535.
    if ($values['title_length'] <= 0 || $values['title_length'] > 65535) {
      $form_state->setErrorByName('title_length', $this->t('The number should be between 1 & 65,535.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('entity_title_length.config');
    if (is_numeric($values['title_length'])) {
      // Change title length in database.
      entity_title_length_changer('node', 'title', $values['title_length']);
      $config
        ->set('title_length', $values['title_length'])
        ->set('entity_name', $values['entity_name'])
        ->save();
    }

  }

}

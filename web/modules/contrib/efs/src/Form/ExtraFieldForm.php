<?php

namespace Drupal\efs\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ExtraFieldForm.
 */
class ExtraFieldForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $extra_field = $this->entity;
    $form['entity_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity type'),
      '#maxlength' => 255,
      '#default_value' => $extra_field->getTargetEntityTypeId(),
      '#required' => TRUE,
    ];

    $form['bundle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bundle'),
      '#maxlength' => 255,
      '#default_value' => $extra_field->getBundle(),
      '#required' => TRUE,
    ];

    $form['context'] = [
      '#type' => 'select',
      '#title' => $this->t('Context'),
      '#options' => ['display' => 'Display', 'form' => 'Form'],
      '#default_value' => $extra_field->getContext(),
      '#required' => TRUE,
    ];

    $form['mode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mode'),
      '#maxlength' => 255,
      '#default_value' => $extra_field->getMode(),
      '#required' => TRUE,
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $extra_field->label(),
      '#description' => $this->t("Label for the Extra field."),
      '#required' => TRUE,
    ];

    if ($extra_field->isNew()) {
      $form['id'] = [
        '#type' => 'machine_name',
        '#default_value' => $extra_field->id(),
        '#machine_name' => [
          'exists' => '\Drupal\efs\Entity\ExtraField::load',
        ],
        '#disabled' => !$extra_field->isNew(),
      ];
    }

    $manager = \Drupal::service('plugin.manager.efs.formatters');
    $plugins = [];
    $definitions = $manager->getDefinitions();
    foreach ($definitions as $id => $def) {
      $plugins[$id] = $def['label'];
    }
    $form['plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Plugin'),
      '#options' => $plugins,
      '#default_value' => $extra_field->getPlugin(),
      '#required' => TRUE,
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $extra_field = $this->entity;
    if ($extra_field->isNew()) {
      $extra_field->set('field_name', $extra_field->id());
      $id = [
        $extra_field->getTargetEntityTypeId(),
        $extra_field->getBundle(),
        $extra_field->getContext(),
        $extra_field->getMode(),
        $extra_field->id(),
      ];
      $extra_field->set('id', implode('.', $id));
    }
    $status = $extra_field->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Extra field.', [
          '%label' => $extra_field->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Extra field.', [
          '%label' => $extra_field->label(),
        ]));
    }
    $form_state->setRedirectUrl($extra_field->toUrl('collection'));
  }

  /**
   * Checks if a field machine name is taken.
   *
   * @param string $value
   *   The machine name, not prefixed.
   * @param array $element
   *   An array containing the structure of the 'field_name' element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether or not the field machine name is taken.
   */
  public function fieldNameExists($value, array $element, FormStateInterface $form_state) {
    // Add the field prefix.
    $field_name = $value;

    $field_storage_definitions = $this->entityManager->getFieldStorageDefinitions($this->entityTypeId);
    return isset($field_storage_definitions[$field_name]);
  }

}

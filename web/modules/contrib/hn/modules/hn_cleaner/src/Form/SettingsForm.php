<?php

namespace Drupal\hn_cleaner\Form;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hn_cleaner.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hn_cleaner_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hn_cleaner.settings');

    $entity_types = \Drupal::entityTypeManager()->getDefinitions();

    $form['entities'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Hide entities'),
    ];

    $form['fields'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Hide fields'),
    ];

    foreach ($entity_types as $entity_type_id => $entity_type) {

      $form['entities']['disable_entity_' . $entity_type_id] = [
        '#type' => 'checkbox',
        '#title' => $entity_type->getLabel() . ' (' . $entity_type_id . ')',
        '#default_value' => in_array($entity_type_id, $config->get('entities')),
      ];

      if ($entity_type->entityClassImplements(FieldableEntityInterface::class)) {

        $form['fields'][$entity_type_id] = [
          '#type' => 'details',
          '#title' => $entity_type->getLabel() . ' (' . $entity_type_id . ')',
          '#states' => [
            'invisible' => [
              ':input[name="disable_entity_' . $entity_type_id . '"]' => ['checked' => TRUE],
            ],
          ],
        ];

        $fields = \Drupal::entityManager()->getBaseFieldDefinitions($entity_type_id);
        foreach ($fields as $field_id => $field) {
          $field_title = $field_id;
          if ($field->getLabel()) {
            $field_title .= ' (' . $field->getLabel() . ')';
          }
          $form['fields'][$entity_type_id]['disable_entity_' . $entity_type_id . '_field_' . $field_id] = [
            '#type' => 'checkbox',
            '#title' => $field_title,
            '#default_value' => !empty($config->get('fields')[$entity_type_id]) && in_array($field_id, $config->get('fields')[$entity_type_id]),
          ];
        }
      }

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $entities = [];

    $fields = [];

    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
      if (!empty($values['disable_entity_' . $entity_type_id])) {
        $entities[] = $entity_type_id;
      }
      if ($entity_type->entityClassImplements(FieldableEntityInterface::class)) {
        foreach (\Drupal::entityManager()->getBaseFieldDefinitions($entity_type_id) as $field_id => $field) {
          if (!empty($values['disable_entity_' . $entity_type_id . '_field_' . $field_id])) {
            $fields[$entity_type_id][] = $field_id;
          }
        }
      }
    }

    // Save the config.
    $this->config('hn_cleaner.settings')
      ->set('entities', $entities)
      ->set('fields', $fields)
      ->save();

    parent::submitForm($form, $form_state);
  }

}

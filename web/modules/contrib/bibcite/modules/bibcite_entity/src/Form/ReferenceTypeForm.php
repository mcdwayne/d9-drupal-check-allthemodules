<?php

namespace Drupal\bibcite_entity\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Reference type form.
 */
class ReferenceTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\bibcite_entity\Entity\ReferenceTypeInterface $reference_type */
    $reference_type = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $reference_type->label(),
      '#description' => $this->t('Label for the Reference type.'),
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#default_value' => $reference_type->getDescription(),
      '#description' => $this->t('Short description of Reference type.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $reference_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\bibcite_entity\Entity\ReferenceType::load',
      ],
      '#disabled' => !$reference_type->isNew(),
    ];

    $form['override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override default properties'),
      '#default_value' => $reference_type->isRequiredOverride(),
    ];

    $form['overrides'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          [':input[name="override"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['overrides']['fields'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field name'),
        $this->t('Label'),
        $this->t('Hint'),
        $this->t('Visible'),
        $this->t('Required'),
      ],
      '#tree' => TRUE,
    ];

    $excluded_fields = [
      'id',
      'uuid',
      'langcode',
      'created',
      'changed',
      'type',
      'author',
    ];

    $fields_configuration = $reference_type->getFields();
    $fields = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions('bibcite_reference', 'bibcite_reference');
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
    foreach ($fields as $field) {
      $field_name = $field->getName();
      if (in_array($field_name, $excluded_fields)) {
        continue;
      }

      $field_configuration = !empty($fields_configuration[$field_name])
        ? $fields_configuration[$field_name]
        : [];

      $form['overrides']['fields'][$field_name] = [
        'name' => [
          '#markup' => new FormattableMarkup('@label (@name)', [
            '@label' => $field->getLabel(),
            '@name' => $field_name,
          ]),
        ],
        'label' => [
          '#type' => 'textfield',
          '#size' => 30,
          '#default_value' => isset($field_configuration['label']) ? $field_configuration['label'] : '',
        ],
        'hint' => [
          '#type' => 'textfield',
          '#size' => 30,
          '#default_value' => isset($field_configuration['hint']) ? $field_configuration['hint'] : $field->getDescription(),
        ],
        'visible' => [
          '#type' => 'checkbox',
          '#default_value' => isset($field_configuration['visible']) ? $field_configuration['visible'] : TRUE,
        ],
        'required' => [
          '#type' => 'checkbox',
          '#default_value' => isset($field_configuration['required']) ? $field_configuration['required'] : FALSE,
        ],
      ];
    }

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\bibcite_entity\Entity\ReferenceTypeInterface $reference_type */
    $reference_type = $this->entity;
    $status = $reference_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Reference type.', [
          '%label' => $reference_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Reference type.', [
          '%label' => $reference_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($reference_type->toUrl('collection'));
  }

}

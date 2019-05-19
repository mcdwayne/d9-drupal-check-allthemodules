<?php

namespace Drupal\visualn_drawing\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VisualNDrawingTypeForm.
 */
class VisualNDrawingTypeForm extends EntityForm {

  const VISUALN_FETCHER_FIELD_TYPE_ID = 'visualn_fetcher';

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $visualn_drawing_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $visualn_drawing_type->label(),
      '#description' => $this->t("Label for the VisualN Drawing type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $visualn_drawing_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\visualn_drawing\Entity\VisualNDrawingType::load',
      ],
      '#disabled' => !$visualn_drawing_type->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('description'),
      '#description' => $this->t("Description for the VisualN Drawing type."),
    ];

    // get the list of visualn_fetcher fields attached to the entity type / bundle
    // also considered  base and bundle fields
    // see ContentEntityBase::bundleFieldDefinitions() and ::baseFieldDefinitions()
    $options = [];

    // @todo: instantiate on create
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $entity_type = $visualn_drawing_type->getEntityType()->getBundleOf();

    // for new drawing type bundle is empty
    $bundle = $visualn_drawing_type->id();
    $bundle_fields = $entityFieldManager->getFieldDefinitions($entity_type, $bundle);

    // for new drawing types it may still contain base fields (e.g. "Default fetcher" field)
    // so do not skip them
    foreach ($bundle_fields as $field_name => $field_definition) {
      if ($field_definition->getType() == static::VISUALN_FETCHER_FIELD_TYPE_ID) {
        $options[$field_name] = $field_definition->getLabel();
      }
    }

    // sort options by name
    asort($options);

    // If entity type is new and visualn_fetcher base (or bundle) fields found (see Drawing entity class)
    // use the first field (generally there is one "Default fetcher" base field) as default.
    reset($options);
    $default_fetcher = $visualn_drawing_type->isNew() && !empty($options) ? key($options) : $this->entity->getDrawingFetcherField();
    $form['drawing_fetcher_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Drawing fetcher field'),
      '#options' => $options,
      '#default_value' => $default_fetcher,
      '#description' => $this->t('The field that is used to provide drawing build.'),
      '#disabled' => $visualn_drawing_type->isNew() && empty($options),
      '#empty_value' => '',
      '#empty_option' => t('- Select drawing fetcher field -'),
      '#required' => !empty($options),
    ];

    $form['thumbnail'] = [
      '#type' => 'details',
      '#title' => $this->t('Thumbnail'),
      '#open' => TRUE,
    ];

    // @todo: validate path or check if files exists, add image upload field
    $form['thumbnail']['thumbnail_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Thumbnail path'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('thumbnail_path'),
      '#description' => $this->t('Examples: <code>@implicit-public-file</code> (for a file in the public filesystem), <code>@explicit-file</code>, or <code>@local-file</code>.', [
        '@implicit-public-file' => 'thumbnail.png',
        '@explicit-file' => 'public://thumbnail.png',
        '@local-file' => 'modules/custom/my_module/thumbnail.png',
      ]),
    ];
    $form['thumbnail']['thumbnail_upload'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload thumbnail'),
      '#maxlength' => 40,
      '#description' => t("If you don't have direct file access to the server, use this field to upload your thumbnail."),
      '#upload_validators' => [
        'file_validate_is_image' => [],
      ],
    ];

    // @note: based on NodeTypeForm::form()
    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
    ];
    $form['workflow'] = [
      '#type' => 'details',
      '#title' => t('Publishing options'),
      '#group' => 'additional_settings',
    ];
    $workflow_options = [
      // @todo: in NodeTypeForm::form() a fake entity is created to get base field default value
      //   here we just set it manually
      //'status' => $drawing->status->value,
      'revision' => $visualn_drawing_type->shouldCreateNewRevision(),
    ];
    // Prepare workflow options to be used for 'checkboxes' form element.
    $keys = array_keys(array_filter($workflow_options));
    $workflow_options = array_combine($keys, $keys);
    $form['workflow']['options'] = [
      '#type' => 'checkboxes',
      '#title' => t('Default options'),
      '#default_value' => $workflow_options,
      '#options' => [
        //'status' => t('Published'),
        'revision' => t('Create new revision'),
      ],
      '#description' => t('Users with the <em>Administer VisualN Drawing entities</em> permission will be able to override these options.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $visualn_drawing_type = $this->entity;
    $visualn_drawing_type->setNewRevision($form_state->getValue(['options', 'revision']));

    $status = $visualn_drawing_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VisualN Drawing type.', [
          '%label' => $visualn_drawing_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VisualN Drawing type.', [
          '%label' => $visualn_drawing_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($visualn_drawing_type->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // @see Drupal\system\Form\ThemeSettingsForm::validateForm()
    $file = _file_save_upload_from_form($form['thumbnail']['thumbnail_upload'], $form_state, 0);
    if ($file) {
      // Put the temporary file in form_values so we can save it on submit.
      $form_state->setValue('thumbnail_upload_value', $file);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $drawing_fetcher_field = $form_state->getValue('drawing_fetcher_field', '');
    $this->entity->set('drawing_fetcher_field', $drawing_fetcher_field);

    // @see Drupal\system\Form\ThemeSettingsForm::submitForm()
    $values = $form_state->getValues();
    if (!empty($values['thumbnail_upload_value'])) {
      $filename = file_unmanaged_copy($values['thumbnail_upload_value']->getFileUri());
      $thumbnail = $filename;
    }
    else {
      $thumbnail = $form_state->getValue('thumbnail_path', '');
    }
    unset($values['thumbnail_upload_value']);

    $this->entity->set('thumbnail_path', $thumbnail);
  }

}

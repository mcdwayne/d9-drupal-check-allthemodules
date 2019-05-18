<?php

namespace Drupal\paragraphs_sets\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Form controller for paragraph set forms.
 */
class ParagraphsSetForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\paragraphs\ParagraphsTypeInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\paragraphs_sets\ParagraphsSetInterface $paragraphs_set */
    $paragraphs_set = $this->entity;

    if (!$paragraphs_set->isNew()) {
      $form['#title'] = $this->t('Edit %title paragraph set', [
        '%title' => $paragraphs_set->label(),
      ]);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $paragraphs_set->label(),
      '#description' => $this->t("Label for the Paragraphs set."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $paragraphs_set->id(),
      '#machine_name' => [
        'exists' => 'paragraphs_set_load',
      ],
      '#maxlength' => 32,
      '#disabled' => !$paragraphs_set->isNew(),
    ];

    $form['icon_file'] = [
      '#title' => $this->t('Paragraphs set icon'),
      '#type' => 'managed_file',
      '#upload_location' => 'public://paragraphs_set_icon/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg svg'],
      ],
    ];

    if ($file = $this->entity->getIconFile()) {
      $form['icon_file']['#default_value'] = ['target_id' => $file->id()];
    }

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $paragraphs_set->getDescription(),
    ];

    $paragraphs_config = '';
    if (!$paragraphs_set->isNew()) {
      $config = $this->config("paragraphs_sets.set.{$paragraphs_set->id()}");
      $paragraphs_config = Yaml::encode(['paragraphs' => $config->get('paragraphs')]);
    }

    $form['paragraphs_config'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paragraphs configuration'),
      '#description' => $this->t('The paragraphs in the set and field default values can be defined in YAML syntax.'),
      '#default_value' => $paragraphs_config,
      '#rows' => 15,
      '#attributes' => [
        'data-yaml-editor' => 'true',
      ],
    ];
    if (!$this->moduleHandler->moduleExists('yaml_editor')) {
      $form['paragraphs_config']['#description'] .= $this->t('<br />For easier editing of the configuration consider installing the <a href="@yaml-editor">YAML Editor</a> module.', ['@yaml-editor' => 'https://www.drupal.org/project/yaml_editor']);
    }

    $config_example = [
      'paragraphs' => [
        [
          'type' => 'text_simple',
          'data' => [
            'field_headline' => 'Build something amazing with Drupal',
          ],
        ],
        [
          'type' => 'text',
          'data' => [
            'field_headline' => 'Paragraphs Sets',
            'field_content' => htmlentities('<p>You may also add some <strong>markup</strong> in the default value ...</p>'),
          ],
        ],
      ],
    ];
    $form['config_example'] = [
      '#type' => 'details',
      '#title' => $this->t('Example configuration'),
    ];
    $form['config_example']['code'] = [
      '#prefix' => '<pre>',
      '#suffix' => '</pre>',
      '#markup' => Yaml::encode($config_example),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $paragraphs_set = $this->entity;

    $icon_file = $form_state->getValue(['icon_file', '0']);
    // Set the file UUID to the paragraph configuration.
    if (!empty($icon_file) && $file = $this->entityTypeManager->getStorage('file')->load($icon_file)) {
      $paragraphs_set->set('icon_uuid', $file->uuid());
    }
    else {
      $paragraphs_set->set('icon_uuid', NULL);
    }

    $paragraphs_config = $form_state->getValue('paragraphs_config') ?: 'paragraphs:';

    try {
      $paragraphs = Yaml::decode($paragraphs_config);
      $form_state->set('paragraphs', empty($paragraphs['paragraphs']) ? [] : $paragraphs['paragraphs']);
    }
    catch (InvalidDataTypeException $e) {
      $form_state->setErrorByName('paragraphs_config', $e->getMessage());
    }

    $types_available = paragraphs_type_get_types();
    foreach ($paragraphs['paragraphs'] as $paragraph_config) {
      if (!isset($types_available[$paragraph_config['type']])) {
        $form_state->setErrorByName('paragraphs_config', $this->t('Unknown paragraph bundle %type', ['%type' => $paragraph_config['type']]));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->set('paragraphs', $form_state->get('paragraphs'));
    parent::save($form, $form_state);

    $this->messenger()->addMessage($this->t('Saved the %label Paragraphs set.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirect('entity.paragraphs_set.collection');
  }

}

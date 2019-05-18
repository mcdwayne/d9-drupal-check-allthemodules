<?php

namespace Drupal\box\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Entity\ContentLanguageSettings;

/**
 * Class BoxTypeForm.
 *
 * @package Drupal\box\Form
 */
class BoxTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\box\Entity\BoxInterface $box */
    /** @var \Drupal\box\Entity\BoxTypeInterface $box_type */
    $box_type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add box type');
      $fields = $this->entityManager->getBaseFieldDefinitions('box');
      // Create a box with a fake bundle using the type's UUID so that we can
      // get the default values for workflow settings.
      // @todo Make it possible to get default values without an entity.
      //   https://www.drupal.org/node/2318187
      // $box = $this->entityManager->getStorage('box')->create(['type' => $box_type->uuid()]);
    }
    else {
      $form['#title'] = $this->t('Edit %label box type', ['%label' => $box_type->label()]);
      $fields = $this->entityManager->getFieldDefinitions('box', $box_type->id());
      // Create a box to get the current values for workflow settings fields.
      // $box = $this->entityManager->getStorage('box')->create(['type' => $box_type->id()]);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $box_type->label(),
      '#description' => $this->t("Label for the Box type. This label will be displayed on the <em>Add box</em> page."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $box_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\box\Entity\BoxType::load',
      ],
      '#disabled' => !$box_type->isNew(),
    ];

    $form['description'] = [
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $box_type->getDescription(),
      '#description' => t('This text will be displayed on the <em>Add box</em> page.'),
    ];

    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['submission'] = [
      '#type' => 'details',
      '#title' => t('Submission form settings'),
      '#group' => 'additional_settings',
      '#open' => TRUE,
    ];
    $form['submission']['title_label'] = [
      '#title' => t('Title field label'),
      '#type' => 'textfield',
      '#default_value' => $fields['title']->getLabel(),
      '#required' => TRUE,
    ];
    $form['workflow'] = [
      '#type' => 'details',
      '#title' => t('Publishing options'),
      '#group' => 'additional_settings',
    ];
    $workflow_options = [
      //'status' => $box->status->value,
      'revision' => $box_type->shouldCreateNewRevision(),
      'require_log' => $box_type->isRevisionLogRequired(),
    ];
    // Prepare workflow options to be used for 'checkboxes' form element.
    $keys = array_keys(array_filter($workflow_options));
    $workflow_options = array_combine($keys, $keys);
    $form['workflow']['options'] = [
      '#type' => 'checkboxes',
      '#title' => t('Default options'),
      '#default_value' => $workflow_options,
      '#options' => [
        // 'status' => t('Published'),
        'revision' => t('Create new revision'),
        'require_log' => t('Require revision log message'),
      ],
    ];
    /*if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => t('Language settings'),
        '#group' => 'additional_settings',
      ];

      $language_configuration = ContentLanguageSettings::loadByEntityTypeBundle('box', $box_type->id());
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'box',
          'bundle' => $box_type->id(),
        ],
        '#default_value' => $language_configuration,
      ];
    }*/

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\box\Entity\BoxTypeInterface $box_type */
    $box_type = $this->entity;
    $box_type->setNewRevision($form_state->getValue(['options', 'revision']));
    if ($form_state->getValue(['options', 'require_log'])) {
      $box_type->setRevisionLogRequired();
    }
    else {
      $box_type->setRevisionLogOptional();
    }

    $status = $box_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Box type.', [
          '%label' => $box_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Box type.', [
          '%label' => $box_type->label(),
        ]));
    }

    $fields = $this->entityManager->getFieldDefinitions('box', $box_type->id());
    // Update title field definition.
    $title_field = $fields['title'];
    $title_label = $form_state->getValue('title_label');
    if ($title_field->getLabel() != $title_label) {
      $title_field->getConfig($box_type->id())->setLabel($title_label)->save();
    }

    $this->entityManager->clearCachedFieldDefinitions();

    $form_state->setRedirectUrl($box_type->toUrl('collection'));
  }

}

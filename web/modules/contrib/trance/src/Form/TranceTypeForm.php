<?php

namespace Drupal\trance\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TranceTypeForm.
 *
 * @package Drupal\trance\Form
 */
class TranceTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $trance_type = $this->entity;
    $entity_type_label = $trance_type->getEntityType()->getLabel();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $trance_type->label(),
      '#description' => $this->t('Label for the @type.', [
        '@type' => $entity_type_label,
      ]),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $trance_type->id(),
      '#machine_name' => [
        'exists' => $trance_type->getEntityType()->getClass() . '::load',
      ],
      '#disabled' => !$trance_type->isNew(),
    ];

    $form['description'] = array(
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $trance_type->getDescription(),
      '#description' => $this->t('This text will be displayed on the <em>Add cms content item</em> page.'),
    );

    $form['help']  = array(
      '#type' => 'textarea',
      '#title' => $this->t('Explanation or submission guidelines'),
      '#default_value' => $trance_type->getHelp(),
      '#description' => $this->t('This text will be displayed at the top of the page when creating or editing a CMS content item of this type.'),
    );

    $form['revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $trance_type->shouldCreateNewRevision(),
      '#description' => $this->t('Create a new revision by default for this component type.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $trance_type = $this->entity;
    $status = $trance_type->save();

    $entity_type_label = $trance_type->getEntityType()->getLabel();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label @type.', [
          '%label' => $trance_type->label(),
          '@type' => $entity_type_label,
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label @type.', [
          '%label' => $trance_type->label(),
          '@type' => $entity_type_label,
        ]));
    }

    $this->entityManager->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($trance_type->urlInfo('collection'));
  }

}

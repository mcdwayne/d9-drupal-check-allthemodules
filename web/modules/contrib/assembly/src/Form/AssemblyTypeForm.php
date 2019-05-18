<?php

namespace Drupal\assembly\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AssemblyTypeForm.
 *
 * @package Drupal\assembly\Form
 */
class AssemblyTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $assembly_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $assembly_type->label(),
      '#description' => $this->t("Label for the Assembly type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $assembly_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\assembly\Entity\AssemblyType::load',
      ],
      '#disabled' => !$assembly_type->isNew(),
    ];

    $form['description'] =[
      '#type' => 'textarea',
      '#title' => 'Description',
      '#default_value' => $assembly_type->description,
    ];

    $form['visual_styles'] =[
      '#type' => 'textarea',
      '#title' => 'Visual Styles',
      '#default_value' => $assembly_type->getVisualStyles(),
      '#description' => 'Provide a list of styles the user can choose. Styles are added as CSS classes when the content bar is rendered. Add one style per line, in the format: <br /><strong><em>CSS class</em>|<em>label</em>|<em>description of style</em></strong>',
    ];

    $form['new_revision'] = [
      '#type' => 'checkbox',
      '#title' => 'Create new revision',
      '#default_value' => $assembly_type->isNewRevision(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $assembly_type = $this->entity;
    $status = $assembly_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Assembly type.', [
          '%label' => $assembly_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Assembly type.', [
          '%label' => $assembly_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($assembly_type->toUrl('collection'));
  }

}

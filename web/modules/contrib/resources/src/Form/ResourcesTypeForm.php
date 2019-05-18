<?php

namespace Drupal\resources\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ResourcesTypeForm.
 */
class ResourcesTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $resources_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $resources_type->label(),
      '#description' => $this->t("Label for the Resources type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $resources_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\resources\Entity\ResourcesType::load',
      ],
      '#disabled' => !$resources_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $resources_type = $this->entity;
    $status = $resources_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Resources type.', [
          '%label' => $resources_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Resources type.', [
          '%label' => $resources_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($resources_type->toUrl('collection'));
  }

}

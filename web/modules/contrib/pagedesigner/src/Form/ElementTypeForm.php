<?php

namespace Drupal\pagedesigner\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ElementTypeForm.
 */
class ElementTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $pagedesigner_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $pagedesigner_type->label(),
      '#description' => $this->t("Label for the Pagedesigner type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $pagedesigner_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\pagedesigner\Entity\ElementType::load',
      ],
      '#disabled' => !$pagedesigner_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $pagedesigner_type = $this->entity;
    $status = $pagedesigner_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Pagedesigner type.', [
          '%label' => $pagedesigner_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Pagedesigner type.', [
          '%label' => $pagedesigner_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($pagedesigner_type->toUrl('collection'));
  }

}

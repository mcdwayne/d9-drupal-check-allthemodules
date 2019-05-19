<?php

namespace Drupal\widget_engine\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WidgetTypeForm.
 *
 * @package Drupal\widget_engine\Form
 */
class WidgetTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $widget_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $widget_type->label(),
      '#description' => $this->t("Label for the Widget type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $widget_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\widget_engine\Entity\WidgetType::load',
      ],
      '#disabled' => !$widget_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $widget_type = $this->entity;
    $status = $widget_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Widget type.', [
          '%label' => $widget_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Widget type.', [
          '%label' => $widget_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($widget_type->toUrl('collection'));
  }

}

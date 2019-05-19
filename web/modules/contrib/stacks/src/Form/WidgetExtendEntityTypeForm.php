<?php

namespace Drupal\stacks\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WidgetExtendEntityTypeForm.
 *
 * @package Drupal\stacks\Form
 */
class WidgetExtendEntityTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $widget_extend_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $widget_extend_type->label(),
      '#description' => t("Label for the Widget Extend type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $widget_extend_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\stacks\Entity\WidgetExtendEntityType::load',
      ],
      '#disabled' => !$widget_extend_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $widget_extend_type = $this->entity;
    $status = $widget_extend_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message(t('Created the %label Widget Extend type.', [
          '%label' => $widget_extend_type->label(),
        ]));
        break;

      default:
        drupal_set_message(t('Saved the %label Widget Extend type.', [
          '%label' => $widget_extend_type->label(),
        ]));
    }

    $form_state->setRedirectUrl($widget_extend_type->urlInfo('collection'));
  }

}

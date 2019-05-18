<?php

namespace Drupal\flashpoint_course_content\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FlashpointCourseContentTypeForm.
 */
class FlashpointCourseContentTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $flashpoint_course_content_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $flashpoint_course_content_type->label(),
      '#description' => $this->t("Label for the Flashpoint course content type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $flashpoint_course_content_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\flashpoint_course_content\Entity\FlashpointCourseContentType::load',
      ],
      '#disabled' => !$flashpoint_course_content_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $flashpoint_course_content_type = $this->entity;
    $status = $flashpoint_course_content_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Flashpoint course content type.', [
          '%label' => $flashpoint_course_content_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Flashpoint course content type.', [
          '%label' => $flashpoint_course_content_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($flashpoint_course_content_type->toUrl('collection'));
  }

}

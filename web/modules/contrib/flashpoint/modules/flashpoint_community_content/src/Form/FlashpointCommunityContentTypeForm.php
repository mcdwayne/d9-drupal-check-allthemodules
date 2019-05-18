<?php

namespace Drupal\flashpoint_community_content\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FlashpointCommunityContentTypeForm.
 */
class FlashpointCommunityContentTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $flashpoint_community_c_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $flashpoint_community_c_type->label(),
      '#description' => $this->t("Label for the Flashpoint community content type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $flashpoint_community_c_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\flashpoint_community_content\Entity\FlashpointCommunityContentType::load',
      ],
      '#disabled' => !$flashpoint_community_c_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $flashpoint_community_c_type = $this->entity;
    $status = $flashpoint_community_c_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Flashpoint community content type.', [
          '%label' => $flashpoint_community_c_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Flashpoint community content type.', [
          '%label' => $flashpoint_community_c_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($flashpoint_community_c_type->toUrl('collection'));
  }

}

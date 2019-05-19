<?php

namespace Drupal\user_agent_class\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UserAgentEntityForm.
 */
class UserAgentEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $user_agent_entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trigger in header'),
      '#maxlength' => 255,
      '#default_value' => $user_agent_entity->label(),
      '#description' => $this->t("Label for the User agent entity."),
      '#required' => TRUE,
    ];

    $form['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class in tag body'),
      '#maxlength' => 255,
      '#default_value' => $user_agent_entity->getClassName(),
      '#description' => $this->t("Class for the User agent in body."),
      '#required' => TRUE,
    ];

    $form['enableCheck'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable to check'),
      '#default_value' => $user_agent_entity->getEnableCheck(),
      '#required' => FALSE,
    ];

    $form['exclude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude'),
      '#maxlength' => 255,
      '#default_value' => $user_agent_entity->getExclude(),
      '#description' => $this->t("Exclude trigger phrase from User-Agent"),
      '#required' => FALSE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $user_agent_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\user_agent_class\Entity\UserAgentEntity::load',
      ],
      '#disabled' => !$user_agent_entity->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $user_agent_entity = $this->entity;
    $status = $user_agent_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label User agent entity.', [
          '%label' => $user_agent_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label User agent entity.', [
          '%label' => $user_agent_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($user_agent_entity->toUrl('collection'));
  }

}

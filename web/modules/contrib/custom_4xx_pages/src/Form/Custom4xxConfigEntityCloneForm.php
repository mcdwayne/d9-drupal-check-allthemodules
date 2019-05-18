<?php

namespace Drupal\custom_4xx_pages\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Custom 4xx Configuration Item entities.
 */
class Custom4xxConfigEntityCloneForm extends EntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $requested_path = \Drupal::service('request_stack')->getMasterRequest()->getRequestUri();
    ksm($requested_path);
    return $this->t('Are you sure you want to clone %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.custom4xx_config_entity.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Clone');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // $custom4xx_config_entity = $this->entity;
    // $custom4xx_config_entity->save();
    
    ksm($requested_path);

    ksm($custom4xx_config_entity);
    ksm($form_state->getValue('new_machine_name'));
    // $new_entity_data = [
    //   'id' => $form_state->getValue('new_machine_name'),
    //   'custom_403_path_to_apply' => $this->entity->custom_403_path_to_apply,
    //   'label' => $form_state->getValue('new_machine_name'),
    //   'custom_403_page_path' => $this->entity->custom_403_page_path,
    //   'custom_4xx_type' => $this->entity->custom_4xx_type,
    // ];

    // // Check existing configs for conflicting id
    // $query = \Drupal::entityQuery('custom4xx_config_entity');
    // $nids = $query->execute();

    // $new_custom4xx_config_entity = \Drupal::entityManager()
    //   ->getStorage('custom4xx_config_entity')
    //   ->create($new_entity_data);
    // $new_custom4xx_config_entity->enforceIsNew(TRUE);
    // $new_custom4xx_config_entity->save();

    drupal_set_message(
      $this->t('content @type: cloned @label.',
        [
          '@type' => $this->entity->bundle(),
          '@label' => $this->entity->label(),
        ]
        )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    ksm($this->entity);

    $form['new_machine_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('New Machine Name'),
      '#maxlength' => 255,
      '#default_value' => '',
      '#description' => $this->t("What should the new machine name be?"),
      '#required' => TRUE,
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

}

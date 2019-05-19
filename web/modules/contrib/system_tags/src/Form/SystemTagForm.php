<?php

namespace Drupal\system_tags\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system_tags\Entity\SystemTag;

/**
 * Class SystemTagForm.
 *
 * @package Drupal\system_tag\Form
 */
class SystemTagForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => 'Provide a label for the System Tag.',
      '#default_value' => $this->entity->label(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => sprintf('%s::load', SystemTag::class),
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();

    if ($status === SAVED_NEW) {
      drupal_set_message($this->t('Created new System Tag %label', [
        '%label' => $this->entity->label(),
      ]));
    }
    else {
      drupal_set_message($this->t('Updated System Tag %label', [
        '%label' => $this->entity->label(),
      ]));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}

<?php

namespace Drupal\webcomponents\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WebComponentEntityForm.
 *
 * @package Drupal\webcomponents\Form
 */
class WebComponentEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $web_component = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $web_component->label(),
      '#description' => $this->t("Label for the Web component entity."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $web_component->id(),
      '#machine_name' => [
        'exists' => '\Drupal\webcomponents\Entity\WebComponentEntity::load',
      ],
      '#disabled' => !$web_component->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $web_component = $this->entity;
    $status = $web_component->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Web component entity.', [
          '%label' => $web_component->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Web component entity.', [
          '%label' => $web_component->label(),
        ]));
    }
    $form_state->setRedirectUrl($web_component->toUrl('collection'));
  }

}

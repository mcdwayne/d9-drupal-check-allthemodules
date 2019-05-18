<?php

namespace Drupal\atinternet\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Level2Form.
 */
class Level2Form extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $level2 = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $level2->label(),
      '#description' => $this->t("Label for the Level2 entity."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'number',
      '#title' => $this->t('Level2 ID'),
      '#maxlength' => 255,
      '#default_value' => $level2->id(),
      '#description' => $this->t("Level2 ID for this entity."),
      '#required' => TRUE
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $level2 = $this->entity;
    $status = $level2->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Level2.', [
          '%label' => $level2->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Level2.', [
          '%label' => $level2->label(),
        ]));
    }
    $form_state->setRedirectUrl($level2->toUrl('collection'));
  }

}

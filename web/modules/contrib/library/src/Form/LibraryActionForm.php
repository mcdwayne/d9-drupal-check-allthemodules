<?php

namespace Drupal\library\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\library\LibraryActionInterface;

/**
 * Class LibraryActionForm.
 *
 * @package Drupal\library\Form
 */
class LibraryActionForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $library_action = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $library_action->label(),
      '#description' => $this->t("Label for the Library action."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $library_action->id(),
      '#machine_name' => [
        'exists' => '\Drupal\library\Entity\LibraryAction::load',
      ],
      '#disabled' => !$library_action->isNew(),
    ];

    $form['action'] = [
      '#title' => 'Action to take',
      '#type' => 'select',
      '#options' => [
        LibraryActionInterface::NO_CHANGE => $this->t('No change in status'),
        LibraryActionInterface::CHANGE_TO_AVAILABLE => $this->t('Change status to available'),
        LibraryActionInterface::CHANGE_TO_UNAVAILABLE => $this->t('Change status to unavailable'),
      ],
      '#default_value' => $library_action->action(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $library_action = $this->entity;
    $status = $library_action->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Library action.', [
          '%label' => $library_action->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Library action.', [
          '%label' => $library_action->label(),
        ]));
    }
    $form_state->setRedirectUrl($library_action->urlInfo('collection'));
  }

}

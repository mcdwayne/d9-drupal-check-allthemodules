<?php

/**
 * @file
 * Contains \Drupal\log\Form\LogTypeForm.
 */

namespace Drupal\log\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LogTypeForm.
 *
 * @package Drupal\log\Form
 */
class LogTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $log_type = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $log_type->label(),
      '#description' => $this->t("Label for the Log type."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $log_type->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\log\Entity\LogType::load',
      ),
      '#disabled' => !$log_type->isNew(),
    );

    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $log_type->getDescription(),
      '#description' => $this->t("Log type description."),
    );

    $form['name_pattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name pattern'),
      '#maxlength' => 255,
      '#default_value' => $log_type->getNamePattern(),
      '#desription' => $this->t('When a log name is auto-generated, this is the naming pattern that will be used. Available tokens are below.'),
      // @todo: There is no need to require pattern here.
      '#required' => TRUE,
    );

    $form['name_edit'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow name editing'),
      '#default_value' => $log_type->isNameEditable(),
      '#description' => t('Check this to allow users to edit log names. Otherwise, log names will always be auto-generated.'),
    );

    $form['done'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically done'),
      '#default_value' => $log_type->isAutomaticallyDone(),
      '#description' => t('Automatically mark logs of this type as "done".'),
    );

    $form['new_revision'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $log_type->isNewRevision(),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $log_type = $this->entity;
    $status = $log_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Log type.', [
          '%label' => $log_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Log type.', [
          '%label' => $log_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($log_type->urlInfo('collection'));
  }

}

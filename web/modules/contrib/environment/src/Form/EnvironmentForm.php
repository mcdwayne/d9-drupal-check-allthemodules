<?php

/**
 * @file
 * Contains Drupal\environment\Form\EnvironmentForm.
 */

namespace Drupal\environment\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EnvironmentForm.
 *
 * @package Drupal\environment\Form
 */
class EnvironmentForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $environment = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $environment->label(),
      '#description' => $this->t("Label for the Environment."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $environment->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\environment\Entity\Environment::load',
      ),
      '#disabled' => !$environment->isNew(),
    );

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#default_value' => $environment->getDescription(),
      '#description' => $this->t("Description for the Environment."),
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $environment = $this->entity;
    $environment->set('description', $form_state->getValue('description'));
    $status = $environment->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label Environment.', array(
        '%label' => $environment->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label Environment was not saved.', array(
        '%label' => $environment->label(),
      )));
    }
    $form_state->setRedirectUrl($environment->urlInfo('collection'));
  }

}

<?php

/**
 * @file
 * Contains Drupal\expressions\Form\ExpressionForm.
 */

namespace Drupal\expressions\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Expression form.
 *
 * @package Drupal\expressions\Form
 */
class ExpressionForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    $expression = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $expression->label(),
      '#description' => $this->t('Label for the Expression.'),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $expression->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\expressions\Entity\Expression::load',
      ),
      '#disabled' => !$expression->isNew(),
    );

    $form['status'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => $expression->getStatus(),
    );

    $form['code'] = array(
      '#type' => 'textarea',
      '#title' => t('Code'),
      '#default_value' => $expression->getCode(),
      '#description' => t('A valid Expression code to be evaluated.')
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $expression = $this->entity;
    $status = $expression->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label Expression.', array(
        '%label' => $expression->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label Expression was not saved.', array(
        '%label' => $expression->label(),
      )));
    }
    $form_state->setRedirectUrl($expression->urlInfo('collection'));
  }

}

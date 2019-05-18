<?php

namespace Drupal\performance_budget\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PerformanceBudgetForm.
 *
 * @package Drupal\performance_budget\Form
 */
class PerformanceBudgetForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $performance_budget = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $performance_budget->label(),
      '#description' => $this->t("Label for the Performance Budget."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $performance_budget->id(),
      '#machine_name' => [
        'exists' => '\Drupal\performance_budget\Entity\PerformanceBudget::load',
      ],
      '#disabled' => !$performance_budget->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $performance_budget = $this->entity;
    $status = $performance_budget->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Performance Budget.', [
          '%label' => $performance_budget->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Performance Budget.', [
          '%label' => $performance_budget->label(),
        ]));
    }
    $form_state->setRedirectUrl($performance_budget->toUrl('collection'));
  }

}

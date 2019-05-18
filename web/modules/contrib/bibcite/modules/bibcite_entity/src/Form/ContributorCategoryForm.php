<?php

namespace Drupal\bibcite_entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ContributorCategoryForm.
 *
 * @package Drupal\bibcite_entity\Form
 */
class ContributorCategoryForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $bibcite_contributor_category = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $bibcite_contributor_category->label(),
      '#description' => $this->t("Label for the Contributor category."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $bibcite_contributor_category->id(),
      '#machine_name' => [
        'exists' => '\Drupal\bibcite_entity\Entity\ContributorCategory::load',
      ],
      '#disabled' => !$bibcite_contributor_category->isNew(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $bibcite_contributor_category = $this->entity;
    $status = $bibcite_contributor_category->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Contributor category.', [
          '%label' => $bibcite_contributor_category->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Contributor category.', [
          '%label' => $bibcite_contributor_category->label(),
        ]));
    }
    $form_state->setRedirectUrl($bibcite_contributor_category->toUrl('collection'));
  }

}

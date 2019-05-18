<?php

namespace Drupal\bibcite_entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ContributorRoleForm.
 *
 * @package Drupal\bibcite_entity\Form
 */
class ContributorRoleForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $bibcite_contributor_role = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $bibcite_contributor_role->label(),
      '#description' => $this->t("Label for the Contributor role."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $bibcite_contributor_role->id(),
      '#machine_name' => [
        'exists' => '\Drupal\bibcite_entity\Entity\ContributorRole::load',
      ],
      '#disabled' => !$bibcite_contributor_role->isNew(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $bibcite_contributor_role = $this->entity;
    $status = $bibcite_contributor_role->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Contributor role.', [
          '%label' => $bibcite_contributor_role->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Contributor role.', [
          '%label' => $bibcite_contributor_role->label(),
        ]));
    }
    $form_state->setRedirectUrl($bibcite_contributor_role->toUrl('collection'));
  }

}
